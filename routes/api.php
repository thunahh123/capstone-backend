<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\RecipeController;

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

Route::controller(UserController::class)->group(function () {
    //get all users
    Route::get('/user', 'getAllUsers');

    //get user by id
    Route::get('/user/{id}','getUser');

    //register
    Route::post('/register','register');

    //login
    Route::post('/login','login');

    //delete user
    Route::delete('/user/delete/{id}','deleteUser') ;

    //update email
    Route::put('/user/updateEmail','updateEmail');

    //update password
    Route::put('/user/updatePW','updatePassword');

    
});

Route::controller(RecipeController::class)->group(function (){
    //add new recipe
    Route::post('/recipe/new','addNewRecipe');

    //get recipe by id
    Route::get('/recipe/find/{id}','getRecipe');

    //search recipe by name
    Route::get('/recipe/searchName/{searchTerm}','searchRecipe');

    //
    Route::get('/recipe/search','filterRecipe');

    //update recipe
    Route::put('/recipe/update','updateRecipe');
    //delete recipe
    Route::delete('/recipe/delete/{id}','deleteRecipe');

    //measurement 
    Route::get('/units','getAllUnits');

    
    
});



Route::controller(IngredientController::class)->group(function (){
    //add new ingredient
    Route::post('/ingredient/new','addNewIngredient');

    // find by id
    Route::get('/ingredient/{id}','getIngredient');

    //search ingredient by name
    Route::get('/ingredient/search/{searchTerm}','searchIngredient');

    //getAll measurement unit
    Route::get('/unit/all','getAllUnits');

    //get unit by id
    Route::get('/unit/{id}','getUnit');

});









