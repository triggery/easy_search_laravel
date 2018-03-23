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

// VIEWS
Route::get('/', 'PageController@index')->name('root');
Route::get('/view/{id}', 'PageController@viewOffer')->name('view-offer');
Route::get('/contacts', function () {
    return view('contacts');
})->name('contacts');

// ROUTE VIEW IMAGE OFFER
Route::get('/media/{filename}', function ($filename) {
        return Image::make(storage_path() . '/app/images/' . $filename)->response();
});

// ACTIONS: SEARCH, GET OFFERS...
Route::get('/search-page',      'SearchController@searchApi')->name('search');
Route::get('/get-offers-by-id', 'PageController@getOffersByCategoryID')->name('get-offers');

// ACTION LANGUAGE
Route::get('/set-lang/{lang}', 'PageController@setLang')->name('lang');

// TEST
Route::get('/test/{id}', 'SearchController@testFunc');
Route::get('/test-subcat/', 'SearchController@testSubcat');
