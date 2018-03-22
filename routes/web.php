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

Route::get('/media/{filename}', function ($filename) {
        return Image::make(storage_path() . '/app/images/' . $filename)->response();
});

Route::get('/', 'PageController@index')->name('root');
Route::get('/search-page', 'SearchController@searchApi')->name('search');
Route::get('/view/{id}', 'PageController@viewOffer');
Route::get('/get-offers-by-id', 'PageController@getOffersByCategoryID');
Route::get('/test/{id}', 'SearchController@testFunc');
Route::get('/test-subcat/', 'SearchController@testSubcat');

Route::get('/contacts', function () {
    return view('contacts');
});


