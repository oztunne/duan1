<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
Artisan::command('app:create-admin', function() {
 $data=['name'=>$this->ask('Tên quản trị viên'),'email'=>$this->ask('Email'),'password'=>$this->secret('Mật khẩu (ít nhất 12 ký tự, gồm chữ và số)')];
 $validator=Validator::make($data,['name'=>'required|max:100','email'=>'required|email|unique:users','password'=>['required',Password::min(12)->letters()->numbers()]]);
 if($validator->fails()) { $this->error($validator->errors()->first()); return 1; }
 $user=new User($data); $user->role='admin'; $user->save(); $this->info('Đã tạo quản trị viên.');
})->purpose('Tạo tài khoản quản trị viên bằng mật khẩu nhập ẩn');

