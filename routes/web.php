<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/report','ReportController@reportCustomer');
Route::get('/reportcity','ReportController@getCitys');

Route::get('/ok','ReportController@ok')->name('teste');

