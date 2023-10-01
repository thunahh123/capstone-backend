<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;


class IngredientController extends Controller
{
    //add new ingredient
    function addNewIngredient(Request $req){
        $newIngredient = new Ingredient;
        $newIngredient->name = strtolower($req->name);
        $newIngredient->save();
        return json_encode('New Ingredient Added');
    }

    //get ingredient by id
    function getIngredient($id){
        return Ingredient::find($id);
    }


    //search ingredient by name
    function searchIngredient($searchTerm){
        return Ingredient::where('name','like','%'.$searchTerm.'%')->get();
    }
}
