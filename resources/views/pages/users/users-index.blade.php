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
                <h1>Users</h1>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Users</h4>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary icon-left btn-icon" data-toggle="modal"
                                data-target="#add-user-modal"><i class="fas fa-plus"></i>Tambah User</button>
                            <div class="float-right">
                                <form @submit.prevent="searchUser">
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control pr-5" placeholder="Search"
                                            v-model="searchUserValue" @input="searchUser">
                                        <button v-if="searchUserValue" type="button" class="btn btn-link position-absolute"
                                            style="right: 45px; top: 50%; transform: translateY(-50%); z-index: 3;"
                                            @click="searchUserValue = ''; searchUser();">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit"><i
                                                    class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table-bordered table">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width: 50px; text-align: center;">#</th>
                                            <th scope="col">Nama</th>
                                            <th scope="col">Username</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Role</th>
                                        </tr>
                                    </thead>
                                    <tbody v-if="loadingUser">
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <span class="spinner-border spinner-border-sm"></span> Memuat data...
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody v-else-if="listUser.length > 0">
                                        <tr v-for="(user, index) in listUser" :key="user.id">
                                            <td v-if="user.id !== 1">
                                                <a class="btn btn-primary btn-action mr-1" data-toggle="tooltip" title="Edit"
                                                    @click="editUser(user.id)">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <a class="btn btn-danger btn-action" data-toggle="tooltip" title="Delete"
                                                    @click="deleteUser(user.id)">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                            <td v-else></td>
                                            <td>@{{ user.name }}</td>
                                            <td>@{{ user.username }}</td>
                                            <td>@{{ user.email }}</td>
                                            <td>
                                                <span v-if="user.is_active" class="badge badge-success">Aktif</span>
                                                <span v-else class="badge badge-secondary">Tidak Aktif</span>
                                            </td>
                                            <td>
                                                <span v-if="user.roles && user.roles.length > 0">
                                                    <span v-for="role in user.roles" class="badge badge-info mr-1">@{{ role.name
                                                        }}</span>
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tbody v-else>
                                        <tr>
                                            <td colspan="5" class="text-center">Data tidak ditemukan.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Tambah User -->
        <div class="modal fade" id="add-user-modal" tabindex="-1" aria-labelledby="add-user-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="add-user-modal-label">Tambah User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="storeUser">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" class="form-control" v-model="postUser.userName">
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" v-model="postUser.userUserName">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" class="form-control" v-model="postUser.userEmail">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" v-model="postUser.userPassword">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select class="form-control" v-model="postUser.role">
                                    <option v-for="role in listRole" :value="role.name">@{{ role.name }}</option>
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

        <!-- Modal Edit User -->
        <div class="modal fade" id="edit-user-modal" tabindex="-1" aria-labelledby="edit-user-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-user-modal-label">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="updateUser">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" class="form-control" v-model="editUserData.userName">
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" v-model="editUserData.userUserName">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" class="form-control" v-model="editUserData.userEmail">
                            </div>
                            <div class="form-group">
                                <label>Password <small>(isi jika ingin mengganti)</small></label>
                                <input type="password" class="form-control" v-model="editUserData.userPassword">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" v-model="editUserData.is_active">
                                    <option :value="true">Aktif</option>
                                    <option :value="false">Tidak Aktif</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select class="form-control" v-model="editUserData.role">
                                    <option v-for="role in listRole" :value="role.name">@{{ role.name }}</option>
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
@endpush

@push('page-scripts')
    @include('pages.users.users-js')
@endpush