@extends('layouts.app')

@section('title', $title)

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
    </style>
@endpush

@section('main')
    <div class="main-content" id="app" v-cloak>
        <section class="section">
            <div class="section-header">
                <h1>{{ $title }}</h1>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Agenda</h4>
                        </div>
                        <div class="card-body">
                            @if (Auth::user()->hasRole('Super Admin') || Auth::user()->can('create agenda'))
                                <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#add-agenda-modal">
                                    <i class="fas fa-plus"></i> Tambah Agenda
                                </button>
                            @endif
                            @if (Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Protokol'))
                                <button class="btn btn-success mb-3 ml-1" data-toggle="modal"
                                    data-target="#upload-agenda-document-modal">
                                    <i class="fas fa-file-upload"></i> Upload Dokumen
                                </button>
                            @endif
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
                                        <a class="btn btn-info" style="margin-top: 30px;"
                                            @click="showAllAgenda(true, true)"><i class="fa fa-sync"></i>
                                            Tampilkan
                                            Semua</a>
                                        @if (Auth::user()->hasRole('Super Admin') || Auth::user()->can('export agenda'))
                                            <a class="btn btn-danger" style="margin-top: 30px;" @click.prevent="exportPdf">
                                                <i class="fas fa-file-pdf"></i> Ekspor
                                            </a>
                                        @endif
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
                                            <th>Sifat</th>
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
                                            <td>
                                                @if (Auth::user()->hasRole('Super Admin') || Auth::user()->can('update agenda'))
                                                    <a class="btn btn-icon btn-sm btn-primary" data-toggle="tooltip"
                                                        title="Edit" @click="editAgenda(agenda.id)">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                @endif
                                                @if (Auth::user()->hasRole('Super Admin') || Auth::user()->can('delete agenda'))
                                                    <a class="btn btn-icon btn-sm btn-danger mt-1" data-toggle="tooltip"
                                                        title="Delete" @click="deleteAgenda(agenda.id)">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                @endif
                                                @if (Auth::user()->hasRole('Super Admin') || Auth::user()->can('upload dokumentasi') || Auth::user()->can('view dokumentasi'))
                                                    <a class="btn btn-icon btn-sm btn-success mt-1" data-toggle="tooltip"
                                                        title="Edit Link Dokumentasi" @click="editLinkDok(agenda.id)">
                                                        <i class="fas fa-camera"></i>
                                                    </a>
                                                @endif
                                            </td>
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
                                                <span v-if="agenda.sifat_agenda === 'publik'"
                                                    class="badge badge-success">Publik</span>
                                                <span v-else class="badge badge-secondary">Privat</span>
                                            </td>
                                            <td>
                                                <span v-if="agenda.is_done === 1" class="badge badge-success">Selesai</span>
                                                <span v-else-if="agenda.is_done === 0"
                                                    class="badge badge-warning">Belum</span>
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
                                        <button class="btn btn-sm btn-primary mr-1"
                                            :disabled="pagination.current_page === 1"
                                            @click="showAllAgenda(false, false, pagination.current_page - 1)">
                                            Prev
                                        </button>

                                        <button class="btn btn-sm btn-primary"
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

        <!-- Modal Tambah Agenda -->
        <div class="modal fade" id="add-agenda-modal" tabindex="-1" aria-labelledby="add-agenda-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="storeAgenda">
                        <div class="modal-header">
                            <h5 class="modal-title" id="add-agenda-modal-label">Tambah Agenda</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Agenda</label>
                                <input type="text" class="form-control" v-model="postAgenda.nama_agenda">
                            </div>
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" class="form-control" v-model="postAgenda.tanggal_agenda">
                            </div>
                            <div class="form-group">
                                <label>Jam Mulai</label>
                                <input type="time" class="form-control" v-model="postAgenda.jam_mulai">
                            </div>
                            <div class="form-group">
                                <label>Jam Selesai</label>
                                <input type="time" class="form-control" v-model="postAgenda.jam_selesai">
                            </div>
                            <div class="form-group">
                                <label>Tempat</label>
                                <input type="text" class="form-control" v-model="postAgenda.tempat_agenda">
                            </div>
                            <div class="form-group">
                                <label>Pakaian</label>
                                <input type="text" class="form-control" v-model="postAgenda.pakaian">
                            </div>
                            <div class="form-group">
                                <label>Sifat Agenda</label>
                                <select class="form-control" v-model="postAgenda.sifat_agenda">
                                    <option value="publik">Publik</option>
                                    <option value="privat">Privat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Pelaksanaan</label>
                                <select class="form-control" v-model="postAgenda.is_done">
                                    <option :value="0">Belum</option>
                                    <option :value="1">Selesai</option>
                                    <option :value="2">Reschedule</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kehadiran</label>
                                <select id="select-jabatan" class="form-control select2" v-model="postAgenda.jabatan_ids"
                                    multiple="">
                                    <option v-for="jabatan in listJabatan" :value="jabatan.id">@{{ jabatan.nama_jabatan }}
                                    </option>
                                </select>
                                <div v-for="(item, idx) in postAgenda.kehadiran" :key="idx" class="input-group mb-2"
                                    style="margin-left: -8px">
                                    <input type="text" class="form-control ml-2" v-model="postAgenda.kehadiran[idx]"
                                        placeholder="Isi jika tidak ada jabatan di atas">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger"
                                            @click="postAgenda.kehadiran.splice(idx,1)"
                                            v-if="postAgenda.kehadiran.length > 1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-success" @click="postAgenda.kehadiran.push('')"
                                            v-if="idx === postAgenda.kehadiran.length-1">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END Modal Tambah Agenda -->

        <!-- Modal Upload Dokumen Agenda -->
        <div class="modal fade" id="upload-agenda-document-modal" tabindex="-1"
            aria-labelledby="upload-agenda-document-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="uploadAgendaDocument">
                        <div class="modal-header">
                            <h5 class="modal-title" id="upload-agenda-document-modal-label">Upload Dokumen Agenda</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Dokumen PDF</label>
                                <input type="file" class="form-control" accept="application/pdf"
                                    @change="selectAgendaDocument">
                            </div>
                            <div v-if="uploadDocument.preview.nama_agenda" class="alert alert-light">
                                <div><strong>Agenda:</strong> @{{ uploadDocument.preview.nama_agenda }}</div>
                                <div><strong>Tanggal:</strong> @{{ uploadDocument.preview.tanggal_agenda }}</div>
                                <div><strong>Jam:</strong> @{{ uploadDocument.preview.jam_mulai }} - @{{ uploadDocument.preview.jam_selesai || 'Selesai' }}</div>
                                <div><strong>Tempat:</strong> @{{ uploadDocument.preview.tempat_agenda }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success" :disabled="uploadDocument.loading">
                                <span v-if="uploadDocument.loading" class="spinner-border spinner-border-sm mr-1"></span>
                                <i v-else class="fas fa-file-upload"></i> Proses Dokumen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END Modal Upload Dokumen Agenda -->

        <!-- Modal Edit Agenda -->
        <div class="modal fade" id="edit-agenda-modal" tabindex="-1" aria-labelledby="edit-agenda-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="updateAgenda">
                        <div class="modal-header">
                            <h5 class="modal-title" id="edit-agenda-modal-label">Edit Agenda</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Agenda</label>
                                <input type="text" class="form-control" v-model="editAgendaData.nama_agenda">
                            </div>
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" class="form-control" v-model="editAgendaData.tanggal_agenda">
                            </div>
                            <div class="form-group">
                                <label>Jam Mulai</label>
                                <input type="time" class="form-control" v-model="editAgendaData.jam_mulai">
                            </div>
                            <div class="form-group">
                                <label>Jam Selesai</label>
                                <input type="time" class="form-control" v-model="editAgendaData.jam_selesai">
                            </div>
                            <div class="form-group">
                                <label>Tempat</label>
                                <input type="text" class="form-control" v-model="editAgendaData.tempat_agenda">
                            </div>
                            <div class="form-group">
                                <label>Pakaian</label>
                                <input type="text" class="form-control" v-model="editAgendaData.pakaian">
                            </div>
                            <div class="form-group">
                                <label>Sifat Agenda</label>
                                <select class="form-control" v-model="editAgendaData.sifat_agenda">
                                    <option value="publik">Publik</option>
                                    <option value="privat">Privat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Pelaksanaan</label>
                                <select class="form-control" v-model="editAgendaData.is_done">
                                    <option :value="0">Belum</option>
                                    <option :value="1">Selesai</option>
                                    <option :value="2">Reschedule</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kehadiran</label>
                                <select id="select-edit-jabatan" class="form-control select2"
                                    v-model="editAgendaData.jabatan_ids" multiple="">
                                    <option v-for="jabatan in listJabatan" :value="jabatan.id">@{{ jabatan.nama_jabatan }}
                                    </option>
                                </select>
                                <div v-for="(item, idx) in editAgendaData.kehadiran" :key="idx" class="input-group mb-2">
                                    <input type="text" class="form-control ml-2" v-model="editAgendaData.kehadiran[idx]"
                                        placeholder="Isi jika tidak ada jabatan di atas">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-danger"
                                            @click="editAgendaData.kehadiran.splice(idx,1)"
                                            v-if="editAgendaData.kehadiran.length > 1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-success"
                                            @click="editAgendaData.kehadiran.push('')"
                                            v-if="idx === editAgendaData.kehadiran.length-1">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END Modal Edit Agenda -->

        <!-- Modal Link Dokumentasi Agenda -->
        <div class="modal fade" id="edit-link-dok-modal" tabindex="-1" aria-labelledby="edit-link-dok-modal-label"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form @submit.prevent="updateLinkDok">
                        <div class="modal-header">
                            <h5 class="modal-title" id="edit-link-dok-modal-label">Edit Link Dokumentasi Agenda</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Agenda</label>
                                <input type="text" class="form-control" v-model="editLinkDokData.nama_agenda" readonly>
                            </div>
                            <div class="form-group">
                                <label>Link Terkait</label>
                                <!-- Untuk yang mengupload dokumentasi  -->
                                @if (Auth::user()->can('view dokumentasi') && Auth::user()->can('upload dokumentasi'))
                                    <div v-for="(link, idx) in editLinkDokData.links" :key="idx" class="input-group mb-2">
                                        <input type="text" class="form-control" v-model="editLinkDokData.links[idx].nama_link"
                                            placeholder="Nama Link" required>
                                        <input type="url" class="form-control" v-model="editLinkDokData.links[idx].url"
                                            placeholder="https://contoh.com" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-icon btn-danger"
                                                @click="editLinkDokData.links.splice(idx,1)"
                                                v-if="editLinkDokData.links.length > 1">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <button type="button" class="btn btn-icon btn-success" @click="tambahLink(idx)"
                                                v-if="idx === editLinkDokData.links.length-1">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <a type="button" class="btn btn-icon btn-primary"
                                                @click="bukaLink(editLinkDokData.links[idx].url)" target="_blank"
                                                v-if="editLinkDokData.links[idx].url != ''">
                                                <i class="fas fa-link"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                <!-- Untuk yang hanya melihat dokumentasi  -->
                                @if (Auth::user()->can('view dokumentasi') && !Auth::user()->can('upload dokumentasi'))
                                <div v-if="editLinkDokData.links.length > 0 && (editLinkDokData.links[0].url != '' && editLinkDokData.links[0].nama_link != '')">
                                    <div v-for="(link, idx) in editLinkDokData.links" :key="idx" class="input-group mb-2">
                                        <div v-if="link.url != '' && link.nama_link != ''">
                                            <a :href="link.url" target="_blank">@{{ link.nama_link }}</a>
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <i class="text-danger">Link belum diupload</i>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            @if (Auth::user()->can('upload dokumentasi'))
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- END Modal Link Dokumentasi Agenda -->
    </div>

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

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>
@endpush

@push('page-scripts')
    @include('pages.dashboard-js')
@endpush
