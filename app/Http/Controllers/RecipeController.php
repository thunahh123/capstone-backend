<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Ingredient;
use App\Models\MeasurementUnit;
use Illuminate\Database\Query\Builder;

class RecipeController extends Controller
{
    //add new recipe
    function addNewRecipe(Request $req){
        $newRecipe = new Recipe;
        $newRecipe->author_id = $req->author_id;
        $newRecipe->cooking_time_in_mins = $req->time;
        $newRecipe->title = $req->title;
        $newRecipe->serving = $req->serving;
        $newRecipe->description = $req->desc;
        $newRecipe->direction = json_encode($req->direction);
        $newRecipe->save();
        return json_encode('New Recipe Added');
    }

    //get recipe by id
    function getRecipe($id){
        $recipe = Recipe::find($id);
        $recipe->direction = json_decode($recipe->direction);
        $recipe->ingredients = RecipeIngredient::where('recipe_id',$id)->get();
        for($i=0;$i<count($recipe->ingredients);$i++){
            $recipe->ingredients[$i]->name = Ingredient::find($recipe->ingredients[$i]->ingredient_id)->name;
            $recipe->ingredients[$i]->unit= MeasurementUnit::find($recipe->ingredients[$i]->unit_id);
            unset($recipe->ingredients[$i]->ingredient_id);
            unset($recipe->ingredients[$i]->recipe_id);
            unset($recipe->ingredients[$i]->created_at);
            unset($recipe->ingredients[$i]->updated_at);
            unset($recipe->ingredients[$i]->unit_id);

        }
        return $recipe;
    }
    //search recipe by name
    function searchRecipe($searchTerm){
        return Recipe::whereRaw('LOWER(title) like ?', '%'.strtolower($searchTerm).'%')->get();
    }

    //filter Recipe by 
    function filterRecipe(Request $req){
        $recipe_matches = RecipeIngredient::select('recipe_id',DB::raw("count(*) as total_matches"))->whereIn('ingredient_id', $req->ingredient_ids)->groupBy('recipe_id')->orderByDesc('total_matches')->get();
        $matches = array();
        foreach($recipe_matches as $match){
             array_push($matches, $match->recipe_id);
         }
        $recipes =  Recipe::whereIn('id', $matches)->where([['cooking_time_in_mins', '>=', $req->min_cook_time], ['cooking_time_in_mins', '<=', $req->max_cook_time],[],[]])->get();
        //$ingre_query = RecipeIngredient::select('recipe_id',DB::raw("count(*) as total_ingredients"))->groupBy('recipe_id')->get() ;
        return $matches;
        //return $ingre_query;

    }

    //delete recipe
    function deleteRecipe($id){
        Recipe::find($id)->delete();
        return json_encode('item deleted');
    }

    //update recipe
    function updateRecipe(Request $req){
        $updateRecipe = Recipe::find($req->id); 
        $updateRecipe->cooking_time_in_mins = $req->new_cooking_time;
        $updateRecipe->title = $req->new_title;
        $updateRecipe->serving = $req->new_serving;
        $updateRecipe->description = $req->new_desc;
        $updateRecipe->direction = json_encode($req->input('new_direction', []));
        $updateRecipe->save();
        return json_encode('recipe updated');
    }
    

    //get all measurements
    function getAllUnits(){
        return MeasurementUnit::all();
    }
}    
