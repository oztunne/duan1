<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\WorkspaceController as Workspace;
Route::view('/','home')->name('home');
Route::middleware('guest')->group(function() {
 Route::view('/login','login')->name('login');
 Route::post('/login',[Workspace::class,'login']);
});
Route::middleware(['auth',\App\Http\Middleware\ActiveUser::class])->group(function() {
 Route::get('/dashboard',[Workspace::class,'dashboard'])->name('dashboard');
 Route::post('/logout',[Workspace::class,'logout'])->name('logout');
 Route::patch('/tasks/{task}',[Workspace::class,'updateTask'])->name('tasks.update');
 Route::view('/account','account')->name('account');
 Route::put('/account/password',[Workspace::class,'password'])->name('password.update');
 Route::middleware(\App\Http\Middleware\AdminOnly::class)->group(function() {
  Route::post('/tasks',[Workspace::class,'createTask'])->name('tasks.store');
  Route::delete('/tasks/{task}',[Workspace::class,'deleteTask'])->name('tasks.destroy');
  Route::get('/users',[Workspace::class,'users'])->name('users');
  Route::post('/users',[Workspace::class,'createUser'])->name('users.store');
  Route::patch('/users/{user}',[Workspace::class,'toggleUser'])->name('users.toggle');
 });
});

