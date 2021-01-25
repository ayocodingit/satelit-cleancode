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

// Route::get('{path}', function () {
//     return file_get_contents(public_path('_nuxt/index.html'));
// })->where('path', '(.*)');


Route::get('/', function () {
    return [
        'App' => 'RESTfulAPI v0.1'
    ];
});
Route::post('import-hasil-lab', 'LabSatelitController@importHasil');
Route::get('/grafik/{path}', 'V1\PCRController@getGrafik')->where('path', '(.*)')->name('grafik.url');
