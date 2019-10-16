<?php

Route::get('/', function () {
    return view('welcome');
});

Route::get('/google-shopping/create/{channelId}/{title}', 'GoogleShoppingController@addItem');

Route::get('/google-shopping/items/{channelId}', 'GoogleShoppingController@getItems')
    ->middleware('cors');

Route::get('/google-shopping/delete/{id}/{channelId}', 'GoogleShoppingController@removeItem')
    ->middleware('cors');

Route::get('/google-shopping/search/{channelId}/{search}', 'GoogleShoppingController@searchItem')
    ->middleware('cors');    
