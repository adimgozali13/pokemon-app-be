<?php

use App\Http\Controllers\Api\PokemonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('pokemon-list', [PokemonController::class, 'index']);
Route::get('get-type', [PokemonController::class, 'getType']);
Route::get('get-ability', [PokemonController::class, 'getAbility']);
Route::post('get-pokemon-detail', [PokemonController::class, 'show']);
Route::post('catch-pokemon', [PokemonController::class, 'catch']);
Route::post('release', [PokemonController::class, 'release']);
Route::post('rename', [PokemonController::class, 'rename']);
Route::get('my-pokemon', [PokemonController::class, 'myPokemons']);
