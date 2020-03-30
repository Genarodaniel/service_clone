<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('reports')->group(function(){
    Route::post('generate','ReportController@Generate')->name('report_generate');
    Route::post('report','ReportController@reportCustomer');
    Route::get('teste', 'ReportController@teste');
    Route::get('ok','ReportController@ok');
});
