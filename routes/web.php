<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExportSettingController;

Route::get('/', [HomeController::class, 'index']);
Route::get('home/agenda/show-all', [DashboardController::class, 'showAll']);

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:create-posts')->group(function () {
        // Routes for users with 'create-posts' permission
    });

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/update', [ProfileController::class, 'update']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles/store', [RoleController::class, 'store']);
    Route::get('/roles/show-all', [RoleController::class, 'showAll']);
    Route::get('/roles/{roleId}', [RoleController::class, 'show']);
    Route::put('/roles/{roleId}', [RoleController::class, 'update']);
    Route::delete('/roles/{roleId}', [RoleController::class, 'destroy']);

    Route::post('/permissions/store', [PermissionController::class, 'store']);
    Route::get('/permissions/show-all', [PermissionController::class, 'showAll']);
    Route::get('/permissions/{permissionId}', [PermissionController::class, 'show']);
    Route::put('/permissions/{permissionId}', [PermissionController::class, 'update']);
    Route::delete('/permissions/{permissionId}', [PermissionController::class, 'destroy']);
    Route::post('/roles/{role}/assign-permission', [RoleController::class, 'assignPermission']);
    Route::post('/roles/{role}/remove-permission', [RoleController::class, 'removePermission']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/show-all', [UserController::class, 'showAll']);
    Route::post('/users/store', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/jabatan', [JabatanController::class, 'index']);
    Route::get('/jabatan/show-all', [JabatanController::class, 'showAll']);
    Route::post('/jabatan/store', [JabatanController::class, 'store']);
    Route::post('/jabatan/update-order', [JabatanController::class, 'updateOrder']);
    Route::get('/jabatan/all', [JabatanController::class, 'getAll']);
    Route::get('/jabatan/{id}', [JabatanController::class, 'show']);
    Route::put('/jabatan/{id}', [JabatanController::class, 'update']);
    Route::delete('/jabatan/{id}', [JabatanController::class, 'destroy']);

    Route::get('/agenda/show-all', [DashboardController::class, 'showAll']);
    Route::post('/agenda/store', [DashboardController::class, 'store']);
    Route::post('/agenda/import-document', [DashboardController::class, 'importDocument']);
    Route::post('/agenda/import-document/store', [DashboardController::class, 'storeImportedDocument']);
    Route::get('/agenda/export-pdf', [DashboardController::class, 'exportPdf'])->name('agenda.exportPdf');
    Route::get('/agenda/{id}', [DashboardController::class, 'show']);
    Route::put('/agenda/{id}', [DashboardController::class, 'update']);
    Route::delete('/agenda/{id}', [DashboardController::class, 'destroy']);
    Route::get('/agenda/{id}/links', [DashboardController::class, 'getAgendaLinks'])->name('agenda.getLinks');
    Route::put('/agenda/{id}/links', [DashboardController::class, 'updateAgendaLinks'])->name('agenda.updateLinks');

    Route::get('/export-setting', [ExportSettingController::class, 'index']);
    Route::get('/export-setting/show', [ExportSettingController::class, 'show']);
    Route::post('/export-setting/store', [ExportSettingController::class, 'store']);
});


Route::get('/auth/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/logout', [AuthController::class, 'logout'])->name('logout');
