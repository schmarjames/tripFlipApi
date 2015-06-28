<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Authentication routes
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

Route::get('/', function () {
    return view('welcome');
});


Route::get('/photos', function () {
    //dd(Carbon\Carbon::now());

    //$photos = new App\Console\Commands\PhotoFilter();


    //$locations = App\LocationQuery::all();
    //$photoData = new App\Console\Commands\PhotoData();
    //$photoData->queryPhotos();

});
