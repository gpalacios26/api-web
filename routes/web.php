<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthMiddleware;

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

// Rutas de Prueba
// Route::get('/test/categorias', 'App\Http\Controllers\PruebaController@testOrmCategory');
// Route::get('/test/posts', 'App\Http\Controllers\PruebaController@testOrmPost');

// Rutas Controlador Usuarios
Route::post('/api/user/register', 'App\Http\Controllers\UserController@register');
Route::post('/api/user/login', 'App\Http\Controllers\UserController@login');
Route::put('/api/user/update', 'App\Http\Controllers\UserController@update')->middleware(ApiAuthMiddleware::class);
Route::post('/api/user/upload', 'App\Http\Controllers\UserController@upload')->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/{filename}', 'App\Http\Controllers\UserController@getImage');
Route::get('/api/user/profile/{id}', 'App\Http\Controllers\UserController@getUser');

// Rutas Controlador Categorias
Route::resource('/api/category', 'App\Http\Controllers\CategoryController');

// Rutas Controlador Post
Route::resource('/api/post', 'App\Http\Controllers\PostController');
Route::post('/api/post/upload', 'App\Http\Controllers\PostController@upload');
Route::get('/api/post/image/{filename}', 'App\Http\Controllers\PostController@getImage');
Route::get('/api/post/category/{id}', 'App\Http\Controllers\PostController@getPostsByCategory');
Route::get('/api/post/user/{id}', 'App\Http\Controllers\PostController@getPostsByUser');
