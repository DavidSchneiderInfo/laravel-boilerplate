<?php

use App\Http\Controllers\HomeController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', static function () {
    return view('welcome');
});

Auth::routes(['verify' => true]);

Route::middleware(['auth'])->group(static function (Router $router) {
    $router->get('/home', [HomeController::class, 'index'])->name('home');
});
