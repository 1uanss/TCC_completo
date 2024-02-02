<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\EtapaController;

use Illuminate\Support\Facades\Route;

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
    return view('home', ['title' => 'Home']);
})->name('home');

Route::get('register', [UserController::class, 'register'])->name('register');
Route::post('register', [UserController::class, 'register_action'])->name('register.action');
Route::get('login', [UserController::class, 'login'])->name('login');
Route::post('login', [UserController::class, 'login_action'])->name('login.action');
Route::get('password', [UserController::class, 'password'])->name('password');
Route::post('password', [UserController::class, 'password_action'])->name('password.action');
Route::get('logout', [UserController::class, 'logout'])->name('logout');

Route::get('etapa', [EtapaController::class, 'index'])->name('etapa');
Route::get('etapa2', [EtapaController::class, 'index2'])->name('etapa2');

Route::get('gerarRelatorio', [EtapaController::class, 'gerarRelatorio'])->name('gerarRelatorio');

Route::post('/processar_dados_teclado', [EtapaController::class, 'store']);
Route::post('/processar_dados_teclado2', [EtapaController::class, 'store2']);
