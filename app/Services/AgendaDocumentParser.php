<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\Process\Process;

class AgendaDocumentParser
{
    public function parse(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $text = $file->getMimeType() === 'application/pdf'
            ? $this->extractPdfText($path)
            : $this->extractImageTextWithOcr($path);

        if ($file->getMimeType() === 'application/pdf' && !$this->hasReadableText($text)) {
            $text = $this->extractPdfTextWithOcr($path);
        }

        if (!$this->hasReadableText($text)) {
            throw new RuntimeException(
                'Teks dokumen tidak terbaca. Pastikan foto jelas, tidak miring, dan Poppler/Tesseract OCR tersedia di server.'
            );
        }

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        $text = $this->normalizeText($text);

        $agenda = [
            'nama_agenda' => $this->parsePerihal($text),
            'tanggal_agenda' => $this->parseTanggal($text),
            'jam_mulai' => null,
            'jam_selesai' => null,
            'tempat_agenda' => $this->parseTempat($text),
            'pakaian' => null,
            'sifat_agenda' => 'publik',
            'is_done' => 0,
            'kehadiran' => $this->parseKehadiran($text),
        ];

        [$agenda['jam_mulai'], $agenda['jam_selesai']] = $this->parsePukul($text);

        $agenda['_missing_fields'] = collect([
            'nama_agenda' => 'nama agenda/perihal',
            'tanggal_agenda' => 'tanggal',
            'jam_mulai' => 'jam mulai',
            'tempat_agenda' => 'tempat',
        ])->filter(fn ($label, $field) => empty($agenda[$field]))->values()->all();

        return $agenda;
    }

    private function extractPdfText(string $path): string
    {
        $text = $this->extractPdfTextWithPhp($path);

        if ($this->hasReadableText($text)) {
            return $text;
        }

        $binary = $this->findBinary('PDFTOTEXT_BINARY', 'pdftotext');

        if (!$binary) {
            return '';
        }

        $process = new Process([$binary, '-layout', $path, '-']);
        $process->setTimeout(20);
        $process->run();

        return $process->isSuccessful() ? $process->getOutput() : '';
    }

    private function extractImageTextWithOcr(string $path): string
    {
        $tesseract = $this->findBinary('TESSERACT_BINARY', 'tesseract');

        if (!$tesseract) {
            return '';
        }

        $results = [];
        foreach ([3, 6, 11] as $pageSegmentationMode) {
            $result = trim($this->runTesseract($tesseract, $path, $pageSegmentationMode));
            if ($result !== '') {
                $results[] = $result;
            }
        }

        return implode("\n\n", array_unique($results));
    }

    private function hasReadableText(string $text): bool
    {
        return preg_match('/[\pL\pN]/u', $text) === 1;
    }

    private function extractPdfTextWithPhp(string $path): string
    {
        try {
            return (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function extractPdfTextWithOcr(string $path): string
    {
        $pdftoppm = $this->findBinary('PDFTOPPM_BINARY', 'pdftoppm');
        $tesseract = $this->findBinary('TESSERACT_BINARY', 'tesseract');

        if (!$pdftoppm || !$tesseract) {
            return '';
        }

        $directory = storage_path('app/private/agenda-ocr/'.Str::uuid());
        File::ensureDirectoryExists($directory);

        try {
            $prefix = $directory.'/page';
            $render = new Process([$pdftoppm, '-png', '-r', '200', $path, $prefix]);
            $render->setTimeout(60);
            $render->run();

            if (!$render->isSuccessful()) {
                return '';
            }

            $text = '';
            foreach (glob($directory.'/page-*.png') ?: [] as $image) {
                $text .= "\n".$this->runTesseract($tesseract, $image);
            }

            return $text;
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function runTesseract(string $binary, string $image, ?int $pageSegmentationMode = null): string
    {
        $language = env('TESSERACT_LANG', 'ind+eng');
        $arguments = [$binary, $image, 'stdout', '-l', $language];

        if ($pageSegmentationMode !== null) {
            array_push($arguments, '--psm', (string) $pageSegmentationMode);
        }

        $process = new Process($arguments);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful()) {
            return $process->getOutput();
        }

        $fallback = new Process([$binary, $image, 'stdout']);
        $fallback->setTimeout(60);
        $fallback->run();

        return $fallback->isSuccessful() ? $fallback->getOutput() : '';
    }

    private function findBinary(string $envName, string $default): ?string
    {
        $configured = env($envName);

        if ($configured && is_executable($configured)) {
            return $configured;
        }

        $process = Process::fromShellCommandline('command -v '.escapeshellarg($default));
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput()) ?: null;
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function parsePerihal(string $text): ?string
    {
        if (preg_match('/\b(?:Perihal|Hal)\s*[:;.\-]?\s*([^\n]+)/iu', $text, $matches)) {
            return $this->cleanAgendaTitle($matches[1]);
        }

        $keywords = 'permohonan|undangan|narasumber|rapat|audiensi|kunjungan|pelantikan|pembukaan|penutupan|'.
            'koordinasi|sosialisasi|monitoring|evaluasi|bimbingan|workshop|seminar|upacara|apel';
        $candidates = collect(preg_split('/\n/', $text) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => mb_strlen($line) >= 8 && mb_strlen($line) <= 180)
            ->filter(fn ($line) => preg_match('/\b(?:'.$keywords.')\b/iu', $line))
            ->sortByDesc(function ($line) {
                $wordCount = preg_match_all('/\pL{3,}/u', $line);
                $digitPenalty = preg_match('/\d/', $line) ? 20 : 0;

                return mb_strlen($line) + ($wordCount * 10) - $digitPenalty;
            });

        if ($candidates->isNotEmpty()) {
            return $this->cleanAgendaTitle($candidates->first());
        }

        return null;
    }

    private function parseTanggal(string $text): ?string
    {
        $labelPattern = '(?:H?ari\s*\/?\s*Tangg(?:al|ai)|Tanggal)';
        $weekdayPattern = '(?:Senin|Selasa|Rabu|Kamis|Jum.?at|Sabtu|Minggu)';

        if (!preg_match('/(?:'.$labelPattern.'\s*[:;.\-]?\s*)?(?:'.$weekdayPattern.'\s*,?\s*)'.
            '(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/iu', $text, $matches)) {
            return null;
        }

        $months = [
            'januari' => '01',
            'februari' => '02',
            'maret' => '03',
            'april' => '04',
            'mei' => '05',
            'juni' => '06',
            'juli' => '07',
            'agustus' => '08',
            'september' => '09',
            'oktober' => '10',
            'november' => '11',
            'desember' => '12',
        ];

        $monthName = Str::lower($matches[2]);
        $monthName = [
            'juti' => 'juli',
            'juii' => 'juli',
            'ju1i' => 'juli',
            'jull' => 'juli',
        ][$monthName] ?? $monthName;
        $month = $months[$monthName] ?? null;

        if (!$month) {
            $closestMonth = collect(array_keys($months))
                ->mapWithKeys(fn ($candidate) => [$candidate => levenshtein($monthName, $candidate)])
                ->filter(fn ($distance) => $distance <= 2)
                ->sort()
                ->keys()
                ->first();
            $month = $closestMonth ? $months[$closestMonth] : null;
        }

        return $month ? sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $month, (int) $matches[1]) : null;
    }

    private function parsePukul(string $text): array
    {
        if (!preg_match('/Pukul\s*[:;.\-]?\s*(\d{1,2})[\.:](\d{2})\s*(?:WIB)?'.
            '(?:\s*[-–]\s*(selesai|\d{1,2}[\.:]\d{2}))?/iu', $text, $matches)) {
            return [null, null];
        }

        $start = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        $end = null;

        if (!empty($matches[3]) && !preg_match('/selesai/iu', $matches[3])) {
            [$hour, $minute] = preg_split('/[\.:]/', $matches[3]);
            $end = sprintf('%02d:%02d', (int) $hour, (int) $minute);
        }

        return [$start, $end];
    }

    private function parseTempat(string $text): ?string
    {
        if (!preg_match(
            '/(?:^|\n)[ \t]*Tempat[ \t]*[:;.\-]?[ \t]+([^\n]+)/iu',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return null;
        }

        $place = trim($matches[1][0]);
        $matchEnd = $matches[0][1] + strlen($matches[0][0]);
        $nextLine = trim(strtok(substr($text, $matchEnd), "\n") ?: '');

        if ($nextLine !== '' && preg_match('/^(?:J(?:l|1|I)|11)[.\s]|^(?:Jalan|Lt|Lantai|Gedung|Ruang|Aula|Kantor)\b/iu', $nextLine)) {
            $nextLine = preg_replace('/^11\./u', 'Jl.', $nextLine) ?? $nextLine;
            $place .= ' '.$nextLine;
        }

        $place = preg_replace('/\bPwr\b/iu', 'PWI', $place) ?? $place;

        return trim(preg_replace('/\s+/', ' ', $place) ?? $place, " .\t\n\r\0\x0B");
    }

    private function cleanAgendaTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title, " .:\t\n\r\0\x0B");
        $title = preg_replace('/\b(?:ermohonan|ohonan)\b/iu', 'Permohonan', $title) ?? $title;
        $title = preg_replace('/\bmenjagi\b/iu', 'Menjadi', $title) ?? $title;
        $title = preg_replace('/\bukw\b/iu', 'UKW', $title) ?? $title;

        return $title;
    }

    private function parseKehadiran(string $text): array
    {
        if (!preg_match('/Kepada\s+Yth,?\s*\n(.+?)(?:\n\s*Di\s*[—-]|\n\s*Tempat)/isu', $text, $matches)) {
            return [''];
        }

        $lines = collect(explode("\n", $matches[1]))
            ->map(fn ($line) => trim($line, " \t\n\r\0\x0B,"))
            ->filter()
            ->values();

        return $lines->isNotEmpty() ? $lines->all() : [''];
    }
}
