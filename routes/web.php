<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// سياسة الخصوصية - صفحة عامة (مفيش auth) بتتفتح على https://qadri-palm.com/policy
// مطلوبة لنشر التطبيق على Google Play / App Store.
Route::get('/policy', function () {
    return view('policy');
});
