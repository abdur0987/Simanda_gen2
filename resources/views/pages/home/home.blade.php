@extends('layouts.general')

@section('title', $title)

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap-social/bootstrap-social.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">

    <style>
        [v-cloak] {
            display: none;
        }

        th,
        td {
            padding-top: 25px !important;
            padding-bottom: 25px !important;
            /* Ubah sesuai kebutuhan, misal 16px atas-bawah dan 12px kiri-kanan */
        }

        /* KEMBALIKAN TABLE FULLCALENDAR KE MODE ASLI */
        .fc table {
            border-collapse: collapse !important;
            width: 100% !important;
            table-layout: fixed !important;
        }

        .fc tr {
            display: table-row !important;
        }

        .fc th,
        .fc td {
            display: table-cell !important;
            width: calc(100% / 7) !important;
            vertical-align: top;
        }

        /* header hari */
        .fc-col-header-cell {
            text-align: center;
        }

        /* isi tanggal */
        .fc-daygrid-day-frame {
            min-height: 50px;
        }

        /* matikan flex dari bootstrap/theme */
        .fc-scrollgrid,
        .fc-scrollgrid-section,
        .fc-scrollgrid-section table {
            display: table !important;
            width: 100% !important;
        }

        /* cegah container nyempit */
        .card,
        .card-body,
        .container-fluid,
        .row,
        [class^="col-"] {
            min-width: 0 !important;
        }

        /* styling untuk hari yang bukan dari bulan saat ini */
        .fc-day-other {
            opacity: 0.5;
        }

        .fc .fc-daygrid-day .fc-daygrid-day-number {
            cursor: pointer !important;
        }

        /* container event */
        .fc-daygrid-event {
            background-color: #e7f1ff;
            /* bootstrap primary soft */
            border: 1px solid #b6d4fe;
            color: #084298;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 0.8rem;
            display: block;
            width: 100%;
        }

        /* custom event layout */
        .fc-event-custom {
            font-size: 0.78rem;
            gap: 4px;
        }

        /* titik event */
        .fc-event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0d6efd;
            /* bootstrap primary */
            flex-shrink: 0;
        }

        /* jam */
        .fc-event-time {
            font-weight: 600;
            color: #0d6efd;
            /* bootstrap primary */
        }

        /* judul */
        .fc-event-title {
            /* overflow: hidden; */
            text-overflow: ellipsis;
            /* white-space: nowrap; */
            font-weight: 500;
            word-break: normal;
            overflow-wrap: break-word;
        }

        /* hover effect */
        .fc-daygrid-event:hover {
            /* transform: scale(1.02);
                transition: 0.15s; */
            background-color: #d0e2ff;
            cursor: pointer;
        }

        /* day cell number */
        .fc-daygrid-day-number {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* header hari */
        .fc-col-header-cell {
            background: #f8f9fa;
            font-weight: 600;
        }

        /* today highlight */
        .fc-day-today {
            background-color: rgba(13, 110, 253, 0.05) !important;
        }

        .fc-h-event .fc-event-main {
            color: #0d6efd;
        }
    </style>
@endpush

@section('main')
    <section class="section" id="app" v-cloak>
        <div class="section-header text-center d-flex flex-column align-items-center justify-content-center"
            style="min-height:120px;position:relative;">
            <div class="d-flex align-items-center justify-content-center">
                <img src="{{ asset('img/kemenag-logo.png') }}" alt="logo" width="80" class="mr-3">
                <span style="font-size: 20px; font-weight: bold;" class="text-center">
                    Sistem Informasi Manajemen Agenda Kanwil Kemenag Lampung
                </span>
            </div>
            @if (auth()->check())
                <a href="{{ url('/dashboard') }}" class="btn btn-primary" style="position: absolute; top: 20px; right: 20px;">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary" style="position: absolute; top: 20px; right: 20px;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            @endif
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Daftar Agenda</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div ref="calendarEl" class="w-100"></div>
                                <!-- <div style="position: relative; width: 100%;"> -->
                            </div>
                        </div>
                        <hr>
                        <input type="text" class="form-control mb-3" placeholder="Cari agenda..."
                            v-model="searchAgendaValue" @input="searchAgenda">
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label for="filter-tanggal">Tanggal</label>
                                <input type="date" class="form-control" v-model="filterTanggal" @change="searchAgenda">
                            </div>
                            <div class="col-md-3">
                                <label for="filter-jabatan">Kehadiran</label>
                                <select id="filter-jabatan" class="form-control select2" v-model="filterJabatanIds"
                                    multiple>
                                    <option v-for="jabatan in listJabatan" :value="jabatan.id">@{{ jabatan.nama_jabatan
                                        }}</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group-append">
                                    <a class="btn btn-info" style="margin-top: 30px;" @click="showAllAgenda(true, true)"><i
                                            class="fa fa-sync"></i>
                                        Tampilkan
                                        Semua</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table-bordered table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Kegiatan</th>
                                        <th>Jam</th>
                                        <th>Tempat</th>
                                        <th>Pakaian</th>
                                        <th>Kehadiran</th>
                                        <th>Dokumentasi</th>
                                        <th>Pelaksanaan</th>
                                    </tr>
                                </thead>
                                <tbody v-if="loadingAgenda">
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <span class="spinner-border spinner-border-sm"></span> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else-if="listAgenda.length > 0">
                                    <tr v-for="(agenda, index) in listAgenda" :key="agenda.id">
                                        <td>@{{ index + 1 }}</td>
                                        <td>@{{ moment(agenda.tanggal_agenda).format('DD/MM/YYYY') }}</td>
                                        <td>@{{ agenda.nama_agenda }}</td>
                                        <td>@{{ moment(agenda.jam_mulai, 'HH:mm:ss').format('HH:mm') }} - @{{
                                            agenda.jam_selesai == null ? 'Selesai' :
                                            moment(agenda.jam_selesai, 'HH:mm:ss').format('HH:mm') }}</td>
                                        <td>@{{ agenda.tempat_agenda }}</td>
                                        <td>
                                            <div v-if="agenda.pakaian == null">
                                                <i class="text-secondary">Belum Ditentukan</i>
                                            </div>
                                            <div v-else>
                                                @{{ agenda.pakaian }}
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                v-if="(agenda.jabatans && agenda.jabatans.length > 0) || (agenda.kehadiran && agenda.kehadiran.length > 0 && JSON.parse(agenda.kehadiran)[0] != null)">
                                                <span v-if="agenda.jabatans && agenda.jabatans.length > 0">
                                                    <span v-for="jabatan in agenda.jabatans"
                                                        class="badge badge-info mr-1">@{{
                                                        jabatan.nama_jabatan }}</span>
                                                </span>
                                                <span v-if="agenda.kehadiran && agenda.kehadiran.length > 0">
                                                    <span v-for="(item, idx) in JSON.parse(agenda.kehadiran)" :key="idx"
                                                        class="badge badge-info mr-1">@{{ item }}</span>
                                                </span>
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <div v-if="agenda.links">
                                                <ul style="margin-left: -20px;">
                                                    <li v-for="link in agenda.links" :key="link.id">
                                                        <a :href="link.url" target="_blank">@{{ link.nama_link }}</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="agenda.is_done === 1" class="badge badge-success">Selesai</span>
                                            <span v-else-if="agenda.is_done === 0" class="badge badge-warning">Belum</span>
                                            <span v-else class="badge badge-info">Reschedule</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="9" class="text-center">Data tidak ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    Halaman @{{ pagination.current_page }} dari @{{ pagination.last_page }}
                                </div>

                                <div>
                                    <button 
                                        class="btn btn-sm btn-primary mr-1"
                                        :disabled="pagination.current_page === 1"
                                        @click="showAllAgenda(false, false, pagination.current_page - 1)">
                                        Prev
                                    </button>

                                    <button 
                                        class="btn btn-sm btn-primary"
                                        :disabled="pagination.current_page === pagination.last_page"
                                        @click="showAllAgenda(false, false, pagination.current_page + 1)">
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/simpleweather/jquery.simpleWeather.min.js') }}"></script>
    <script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/jquery.vmap.min.js') }}"></script>
    <script src="{{ asset('library/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('library/summernote/dist/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('library/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
@endpush

@push('page-scripts')
    @include('pages.home.home-js')
@endpush