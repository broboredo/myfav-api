<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api')->post('/sitcom', 'Api@storeSitcom');

Route::middleware('api')->get('/characters', 'Api@getNext');
Route::middleware('api')->get('/all-chars', 'Api@allCharacters');

Route::middleware('api')->get('/vote', 'Api@ranking');
Route::middleware('api')->post('/vote', 'Api@vote');

Route::middleware('api')->get('/appearances', 'Api@appearances');

Route::middleware('api')->get('/command', 'Api@runCommand');

Route::middleware('api')->get('/a', 'Api@test');
