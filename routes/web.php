<?php

use Illuminate\Support\Facades\Route;

//Route::get('login', function () { return view('auth.login'); })->name('login');
Route::post('login', 'Auth\LoginController@login')->name('form.login');
Route::post('login/mfa', 'Auth\LoginController@actionValidateMFACode')->name('form.mfa.code');