@extends('layouts.app')

@section('title', $title)

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/jqvmap/dist/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('library/summernote/dist/summernote-bs4.min.css') }}">

    <style>
        [v-cloak] {
            display: none;
        }
    </style>
@endpush

@section('main')
    <div class="main-content" id="app" v-cloak>
        <section class="section">
            <div class="section-header">
                <h1>Jabatan</h1>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Jabatan</h4>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary icon-left btn-icon" data-toggle="modal"
                                data-target="#add-jabatan-modal"><i class="fas fa-plus"></i>Tambah Jabatan</button>
                            <div class="float-right">
                                <form @submit.prevent="searchJabatan">
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control pr-5" placeholder="Search"
                                            v-model="searchJabatanValue" @input="searchJabatan">
                                        <button v-if="searchJabatanValue" type="button"
                                            class="btn btn-link position-absolute"
                                            style="right: 45px; top: 50%; transform: translateY(-50%); z-index: 3;"
                                            @click="searchJabatanValue = ''; searchJabatan();">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit"><i
                                                    class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <table class="table-bordered table">
                                <thead>
                                    <tr>
                                        <th style="width:30px;"></th>
                                        <th scope="col" style="width: 50px; text-align: center;">#</th>
                                        <th scope="col">Nama Jabatan</th>
                                        <th scope="col">Deskripsi</th>
                                        <th scope="col">Tampil?</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody v-if="loadingJabatan">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <span class="spinner-border spinner-border-sm"></span> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else-if="listJabatan.length > 0" id="jabatan-table-body">
                                    <tr v-for="(jabatan, index) in listJabatan" :key="jabatan.id" :data-id="jabatan.id">
                                        <td><span class="handle" style="cursor:grab;"><i class="fas fa-bars"></i></span>
                                        </td>
                                        <td>@{{ index+1 }}</td>
                                        <td>@{{ jabatan.nama_jabatan }}</td>
                                        <td>@{{ jabatan.deskripsi }}</td>
                                        <td>
                                            <span v-if="jabatan.is_show" class="badge badge-success">Ya</span>
                                            <span v-else class="badge badge-secondary">Tidak</span>
                                        </td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-primary btn-action mr-1 d-inline-block" data-toggle="tooltip"
                                                title="Edit" @click="editJabatan(jabatan.id)">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a class="btn btn-danger btn-action d-inline-block" data-toggle="tooltip"
                                                title="Delete" @click="deleteJabatan(jabatan.id)">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="4" class="text-center">Data tidak ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Tambah Jabatan -->
        <div class="modal fade" id="add-jabatan-modal" tabindex="-1" aria-labelledby="add-jabatan-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="add-jabatan-modal-label">Tambah Jabatan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="storeJabatan">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Jabatan</label>
                                <input type="text" class="form-control" v-model="postJabatan.nama_jabatan">
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea class="form-control" v-model="postJabatan.deskripsi" rows="4"
                                    style="min-height:100px;"></textarea>
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

        <!-- Modal Edit Jabatan -->
        <div class="modal fade" id="edit-jabatan-modal" tabindex="-1" aria-labelledby="edit-jabatan-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-jabatan-modal-label">Edit Jabatan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="updateJabatan">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Jabatan</label>
                                <input type="text" class="form-control" v-model="editJabatanData.nama_jabatan">
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea class="form-control" v-model="editJabatanData.deskripsi" rows="4"
                                    style="min-height:100px;"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Tampilkan Jabatan?</label>
                                <select class="form-control" v-model="editJabatanData.is_show">
                                    <option :value="true">Ya</option>
                                    <option :value="false">Tidak</option>
                                </select>
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
    
    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/index-0.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@push('page-scripts')
    @include('pages.jabatan.jabatan-js')
@endpush