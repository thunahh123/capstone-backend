<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;
use Illuminate\Support\Facades\Log;


class IngredientController extends Controller
{
    public function __construct(Request $req)
    {
        Log::debug("Ingredient Controller", ["Request" => $req, "Body" => $req->all()]);
    }

    //add new ingredient
    function addNewIngredient(Request $req)
    {
        if(Ingredient::firstWhere('name', $req->name)){
            return json_encode(['status' => 'fail', 'message' => 'Ingredient already exists']);
        }
        $newIngredient = new Ingredient;
        $newIngredient->name = strtolower($req->name);
        $newIngredient->save();
        return json_encode(['status' => 'success', 'message' => 'New Ingredient Added']);
    }

    //get ingredient by id
    function getIngredient($id)
    {
        return Ingredient::find($id);
    }


    //search ingredient by name
    function searchIngredient($searchTerm)
    {
        return Ingredient::where('name', 'like', '%' . $searchTerm . '%')->get();
    }
}
