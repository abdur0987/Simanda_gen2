@extends('layouts.app')

@section('title', $title)

@push('style')
    
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
                <h1>Profile</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item">Profile</div>
                </div>
            </div>
            <div class="section-body">
                

                <div class="row">
                    <div class="col-1 col-md-1 col-lg-3"></div>
                    <div class="col-12 col-md-12 col-lg-7">
                        <div class="card">
                            <form @submit.prevent="updateProfile" class="needs-validation" novalidate="">
                                <div class="card-header">
                                    <h4>Edit Profile</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-12">
                                            <label>Nama</label>
                                            <input type="text"
                                                class="form-control"
                                                v-model="profileData.name"
                                                required="">
                                            <div class="invalid-feedback">
                                                Please fill in the first name
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-12">
                                            <label>Email</label>
                                            <input type="email"
                                                class="form-control"
                                                v-model="profileData.email"
                                                required="">
                                            <div class="invalid-feedback">
                                                Please fill in the email
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-12">
                                            <label>Password <small>(isi jika ingin mengganti)</small></label>
                                            <input type="password"
                                                class="form-control"
                                                v-model="profileData.password">
                                            <div class="invalid-feedback">
                                                Please fill in the email
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button class="btn btn-primary" :disabled="loadingProfile">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-1 col-md-1 col-lg-3"></div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/summernote/dist/summernote-bs4.js') }}"></script>

    <!-- Page Specific JS File -->
@endpush

@push('page-scripts')
    @include('pages.profile.profile-js')
@endpush