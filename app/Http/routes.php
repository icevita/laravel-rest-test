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

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'api/v1'], function () {

    Route::get('users', [
        'middleware' => 'auth.basic',
        'uses'       => 'UserController@index'
    ]);

    Route::post('users', [
        'uses' => 'UserController@store'
    ]);

    Route::delete('users',[
        'uses' => 'UserController@delete'
    ]);

    Route::get('users/friends', [
        'uses' => 'UserController@getFriends'
    ]);

    Route::post('users/friends/{id}', [
        'uses' => 'UserController@addRequest'
    ]);

    Route::get('users/friends/requests', [
        'uses' => 'UserController@getRequests'
    ]);
    Route::put('users/friends/{id}/accept', [
        'uses' => 'UserController@acceptRequest'
    ]);
    Route::put('users/friends/{id}/decline', [
        'uses' => 'UserController@declineRequest'
    ]);
});

Route::controllers([
    'auth'     => 'Auth\AuthController',
    'password' => 'Auth\PasswordController',
]);
