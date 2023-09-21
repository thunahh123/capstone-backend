<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

Route::get('/', function(){
    return 'Hello world';
});

//get all users
Route::get('/user', [UserController::class, 'getAllUsers']);

//get user by id
Route::get('/user/{id}', [UserController::class,'getUser']);

//register
Route::post('/register',[UserController::class,'register']);

//login
Route::post('/login',[UserController::class,'login']);

//delete user
Route::delete('/user/delete/{id}',[UserController::class,'deleteUser']) ;

//update email
Route::put('/user/updateemail',[UserController::class,'updateEmail']);

//update password
Route::put('/user/updatepw',[UserController::class,'updatePassword']);