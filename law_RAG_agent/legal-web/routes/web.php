<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. 首页逻辑
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('chat.index');
    }
    return view('welcome');
});

// 2. 移除旧的 Dashboard
Route::get('/dashboard', function () {
    return redirect()->route('chat.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// 3. 需要认证的路由
Route::middleware('auth')->group(function () {

    // 个人资料
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI 聊天核心路由组
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/create', [ChatController::class, 'create'])->name('create');
        Route::get('/{id}', [ChatController::class, 'index'])->name('show');
        Route::post('/{id}/stream', [ChatController::class, 'stream'])->name('stream');

        // 🌟 新增：专门接收前端传来的完整 AI 回复并存库
        Route::post('/{id}/save-message', [ChatController::class, 'saveMessage'])->name('save-message');

        // 关键所在：必须有这一行，对应的名称才是 chat.destroy
        Route::delete('/{id}', [ChatController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__ . '/auth.php';
