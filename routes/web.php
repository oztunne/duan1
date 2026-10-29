<?php

use App\Http\Controllers\OnikiController;
use App\Http\Controllers\WorkspaceController as Workspace;
use App\Http\Middleware\ActiveUser;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', [OnikiController::class, 'home'])->name('home');
Route::get('/listing-images/{listing}', [OnikiController::class, 'image'])->name('content.image');
Route::prefix('{locale}')->where(['locale' => 'vi|ja'])->group(function () {
    Route::get('/find-home',[OnikiController::class,'finder'])->name('oniki.finder');
    Route::get('/property-map',[OnikiController::class,'mapPage'])->name('oniki.property-map');
    Route::get('/property-points', [OnikiController::class, 'mapPoints'])->name('oniki.points');
    Route::get('/', [OnikiController::class, 'home'])->name('oniki.home');
    Route::get('/listings/{kind}', [OnikiController::class, 'index'])->where('kind', 'property|job')->name('oniki.index');
    Route::get('/listings/{kind}/{listing}', [OnikiController::class, 'show'])->where('kind', 'property|job')->name('oniki.show');
    Route::view('/about', 'oniki.about')->name('oniki.about');
    Route::view('/privacy', 'oniki.privacy')->name('oniki.privacy');
    Route::get('/contact', [OnikiController::class, 'contact'])->name('oniki.contact');
    Route::post('/contact', [OnikiController::class, 'inquiry'])->middleware('throttle:5,1')->name('oniki.inquiry');
});
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::post('/login', [Workspace::class, 'login']);
});
Route::middleware(['auth', ActiveUser::class])->group(function () {
    Route::post('/content/property-address', \App\Http\Controllers\PropertyAddressController::class)->middleware('throttle:10,1')->name('content.property-address');
    Route::get('/content', [OnikiController::class, 'manage'])->name('content.index');
    Route::get('/content/create', [OnikiController::class, 'edit'])->name('content.create');
    Route::post('/content', [OnikiController::class, 'save'])->name('content.store');
    Route::get('/content/{listing}/edit', [OnikiController::class, 'edit'])->name('content.edit');
    Route::put('/content/{listing}', [OnikiController::class, 'save'])->name('content.update');
    Route::delete('/content/{listing}', [OnikiController::class, 'remove'])->name('content.delete');
    Route::get('/dashboard', [Workspace::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [Workspace::class, 'logout'])->name('logout');
    Route::patch('/tasks/{task}', [Workspace::class, 'updateTask'])->name('tasks.update');
    Route::view('/account', 'account')->name('account');
    Route::put('/account/password', [Workspace::class, 'password'])->name('password.update');
    Route::middleware(AdminOnly::class)->group(function () {
        Route::get('/inquiries', [OnikiController::class, 'inquiries'])->name('inquiries.index');
        Route::patch('/inquiries/{inquiry}', [OnikiController::class, 'handled'])->name('inquiries.update');
        Route::post('/tasks', [Workspace::class, 'createTask'])->name('tasks.store');
        Route::delete('/tasks/{task}', [Workspace::class, 'deleteTask'])->name('tasks.destroy');
        Route::get('/users', [Workspace::class, 'users'])->name('users');
        Route::post('/users', [Workspace::class, 'createUser'])->name('users.store');
        Route::patch('/users/{user}',[Workspace::class, 'toggleUser'])->name('users.toggle');
    });
});
