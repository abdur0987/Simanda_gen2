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
        $text = $this->extractPdfText($file->getRealPath());

        if (!$this->hasReadableText($text)) {
            $text = $this->extractPdfTextWithOcr($file->getRealPath());
        }

        if (!$this->hasReadableText($text)) {
            throw new RuntimeException(
                'Teks dokumen tidak terbaca. Untuk PDF hasil scan, install Poppler dan Tesseract OCR di server.'
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

        $missing = collect([
            'nama_agenda' => 'nama agenda/perihal',
            'tanggal_agenda' => 'tanggal',
            'jam_mulai' => 'jam mulai',
            'tempat_agenda' => 'tempat',
        ])->filter(fn ($label, $field) => empty($agenda[$field]))->values();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Dokumen terbaca, tetapi field berikut belum ditemukan: '.$missing->implode(', ').'.');
        }

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

    private function runTesseract(string $binary, string $image): string
    {
        $language = env('TESSERACT_LANG', 'ind+eng');
        $process = new Process([$binary, $image, 'stdout', '-l', $language]);
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
        if (preg_match('/Perihal\s*:\s*(.+)/iu', $text, $matches)) {
            return trim($matches[1], " .\t\n\r\0\x0B");
        }

        return null;
    }

    private function parseTanggal(string $text): ?string
    {
        if (!preg_match('/Hari\/Tanggal\s*:\s*(?:[A-Za-z]+,\s*)?(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/iu', $text, $matches)) {
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

        $month = $months[Str::lower($matches[2])] ?? null;

        return $month ? sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $month, (int) $matches[1]) : null;
    }

    private function parsePukul(string $text): array
    {
        if (!preg_match('/Pukul\s*:\s*(\d{1,2})[\.:](\d{2})\s*(?:WIB|Wib|wib)?(?:\s*[-–]\s*(selesai|\d{1,2}[\.:]\d{2}))?/iu', $text, $matches)) {
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
        if (!preg_match('/Tempat\s*:\s*(.+?)(?=\n\s*(?:Perlu|Demikian|Atas|Hormat|$))/isu', $text, $matches)) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $matches[1]) ?? $matches[1], " .\t\n\r\0\x0B");
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
