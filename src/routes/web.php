<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\CorrectionRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*

|--------------------------------------------------------------------------
| 一般ユーザー向け（ログイン ＋ メール認証必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/attendance/list', [AttendanceRecordController::class, 'list'])->name('attendance.list');
    // 勤怠メイン
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');

    // 打刻操作
    Route::prefix('attendance')->group(function () {
        Route::post('/work-start', [AttendanceRecordController::class, 'workStart']);
        Route::post('/work-end', [AttendanceRecordController::class, 'workEnd']);
        Route::post('/rest-start', [AttendanceRecordController::class, 'restStart']);
        Route::post('/rest-end', [AttendanceRecordController::class, 'restEnd']);

        // 詳細・修正申請
        Route::get('/detail/{id?}', [AttendanceRecordController::class, 'show'])->name('attendance.detail');
        Route::post('/update/{id?}', [AttendanceRecordController::class, 'update'])->name('attendance.update');
        Route::get('/correction-requests', [CorrectionRequestController::class, 'index'])->name('attendance.requests');
        Route::get('/attendance/create', [AttendanceRecordController::class, 'adminCreate'])->name('admin.attendance.create');
    });
});

/*

|--------------------------------------------------------------------------
| 管理者向け（ログイン ＋ adminミドルウェア必須）
|--------------------------------------------------------------------------
*/
// 管理者ログイン（未ログイン時のみ）
Route::get('/admin/login', [UserController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('admin.login');

// --- 修正箇所：prefix('admin') の外に記述 ---
Route::middleware(['auth', 'admin'])->group(function () {
    // URLを直接指定
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'index'])
        ->name('admin.application.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [UserController::class, 'index'])->name('admin.attendance.list');
    Route::get('/staff/list', [UserController::class, 'staffIndex'])->name('admin.staff.list');

    // ダッシュボード・スタッフ管理
    Route::get('/dashboard', [UserController::class, 'index'])->name('admin.dashboard');
    Route::get('/staff', [UserController::class, 'index'])->name('admin.index');

    // 勤怠確認・CSV
    Route::get('/attendance/staff/{id}', [UserController::class, 'staffDetail'])->name('admin.attendance.staff');
    Route::get('/attendance/staff/{id}/csv', [UserController::class, 'exportCsv'])->name('admin.attendance.csv');

    // 勤怠修正（管理者直接）
    Route::get('/attendance/{id}', [AttendanceRecordController::class, 'adminEdit'])->name('admin.attendance.edit');
   Route::match(['patch', 'post'], '/attendance/update/{id?}', [AttendanceRecordController::class, 'adminUpdate'])->name('admin.attendance.update');

    // 申請（承認）管理
    Route::get('/applications', [CorrectionRequestController::class, 'index'])->name('admin.application.index');
    Route::get('/application/{id}', [CorrectionRequestController::class, 'show'])->name('admin.application.show');
    Route::patch('/application/{id}/approve', [CorrectionRequestController::class, 'approve'])->name('admin.application.approve');
});

// その他
Route::get('/mail', [UserController::class, 'user.verify-email']);

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect('/login'); // ここでログイン画面を指定
})->name('logout');
