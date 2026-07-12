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
                <h1>Roles</h1>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Roles</h4>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary icon-left btn-icon" data-toggle="modal"
                                data-target="#add-modal"><i class="fas fa-plus"></i>Tambah Role</button>
                            <div class="float-right">
                                <form @submit.prevent="searchRole">
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control pr-5" placeholder="Search"
                                            v-model="searchValue" @input="searchRole">
                                        <button v-if="searchValue" type="button" class="btn btn-link position-absolute"
                                            style="right: 45px; top: 50%; transform: translateY(-50%); z-index: 3;"
                                            @click="searchValue = ''; searchRole();">
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
                                        <th scope="col" style="width: 50px; text-align: center;">#</th>
                                        <th scope="col">Nama Role</th>
                                        <th scope="col">Permissions</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody v-if="loadingRole">
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <span class="spinner-border spinner-border-sm"></span> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else-if="listRole.length > 0">
                                    <tr v-for="(role, index) in listRole" :key="role.id">
                                        <td>@{{ index+1 }}</td>
                                        <td>@{{ role.name }}</td>
                                        <td>
                                            <span v-if="role.permissions && role.permissions.length > 0">
                                                <span v-for="(permission, pIdx) in role.permissions" :key="permission.id"
                                                    class="badge badge-info mr-1">
                                                    @{{ permission.name }}
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-1"
                                                        title="Hapus Permission dari Role"
                                                        @click="removePermissionFromRole(role.id, permission.id)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </span>
                                            </span>
                                            <span v-else class="text-muted">Belum ada permission</span>
                                        </td>
                                        <td>
                                            <a class="btn btn-success btn-action mr-1" data-toggle="tooltip"
                                                title="Kelola Permission" @click="openAssignPermission(role)">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            <template v-if="role.name !== 'Super Admin'">
                                                <a class="btn btn-primary btn-action mr-1" data-toggle="tooltip"
                                                    title="Edit" @click="editRole(role.id)">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <a class="btn btn-danger btn-action" data-toggle="tooltip" title="Delete"
                                                    @click="deleteRole(role.id)">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="3" class="text-center">Data tidak ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Daftar Permissions</h4>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary icon-left btn-icon" data-toggle="modal"
                                data-target="#add-permission-modal"><i class="fas fa-plus"></i>Tambah Permission</button>
                            <div class="float-right">
                                <form @submit.prevent="searchPermission">
                                    <div class="input-group position-relative">
                                        <input type="text" class="form-control pr-5" placeholder="Search"
                                            v-model="searchPermissionValue" @input="searchPermission">
                                        <button v-if="searchPermissionValue" type="button"
                                            class="btn btn-link position-absolute"
                                            style="right: 45px; top: 50%; transform: translateY(-50%); z-index: 3;"
                                            @click="searchPermissionValue = ''; searchPermission();">
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
                                        <th scope="col" style="width: 50px; text-align: center;">#</th>
                                        <th scope="col">Nama Permission</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody v-if="loadingPermission">
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <span class="spinner-border spinner-border-sm"></span> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else-if="listPermission.length > 0">
                                    <tr v-for="(permission, index) in listPermission" :key="permission.id">
                                        <td>@{{ index+1 }}</td>
                                        <td>@{{ permission.name }}</td>
                                        <td>
                                            <a class="btn btn-primary btn-action mr-1" data-toggle="tooltip" title="Edit"
                                                @click="editPermission(permission.id)">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a class="btn btn-danger btn-action" data-toggle="tooltip" title="Delete"
                                                @click="deletePermission(permission.id)">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="3" class="text-center">Data tidak ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Tambah Role -->
        <div class="modal fade" id="add-modal" tabindex="-1" aria-labelledby="add-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="add-modal-label">Tambah Role</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="storeRole">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Role</label>
                                <input type="text" class="form-control form-control-md" v-model="post.roleName">
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

        <!-- Modal Edit Role -->
        <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-modal-label">Edit Role</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="updateRole">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Role</label>
                                <input type="text" class="form-control form-control-md" v-model="edit.roleName">
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

        <!-- Modal Tambah Permission -->
        <div class="modal fade" id="add-permission-modal" tabindex="-1" aria-labelledby="add-permission-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="add-permission-modal-label">Tambah Permission</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="storePermission">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Permission</label>
                                <input type="text" class="form-control form-control-md"
                                    v-model="postPermission.permissionName">
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

        <!-- Modal Edit Permission -->
        <div class="modal fade" id="edit-permission-modal" tabindex="-1" aria-labelledby="edit-permission-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit-permission-modal-label">Edit Permission</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="updatePermission">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nama Permission</label>
                                <input type="text" class="form-control form-control-md"
                                    v-model="editPermissionData.permissionName">
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

        <!-- Modal Assign Permission ke Role -->
        <div class="modal fade" id="assign-permission-modal" tabindex="-1" aria-labelledby="assign-permission-modal-label"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assign-permission-modal-label">Tambah Permission ke Role</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="assignPermissionToRole">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Pilih Permission</label>
                                <select class="form-control" v-model="selectedPermissionId">
                                    <option v-for="permission in listPermission" :value="permission.id">@{{ permission.name
                                        }}
                                    </option>
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
    <!-- <script type="module" src="{{ asset('./js/page_custom/role.js') }}"></script> -->
    @include('pages.role.role-js')
@endpush