<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\MeasurementUnit;
use App\Models\SavedRecipe;
use Illuminate\Database\Query\JoinClause;
use App\Models\Session;

class RecipeController extends Controller
{
    public function __construct(Request $req){
        Log::debug("Recipe Controller",["Request" => $req, "Body" => $req->all()]);
    }

    //add new recipe
    function addNewRecipe(Request $req){
        $newRecipe = new Recipe;  
        $session = Session::firstWhere("session_key","=",$req->session_key);  
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }
        $newRecipe->author_id = $session->user_id;
        $newRecipe->cooking_time_in_mins = $req->time;
        $newRecipe->title = $req->title;
        $newRecipe->serving = $req->serving;
        $newRecipe->description = $req->desc;
        $newRecipe->direction = json_encode($req->direction); 
        $newRecipe->save();       
        //loop? and set recipe_ingredient  
        foreach($req->ingredients as $ingredient){
            DB::statement("INSERT INTO recipe_ingredients(recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)", 
                [$newRecipe->id,
                 $ingredient["id"], 
                 $ingredient["quantity"], 
                 $ingredient["unit_id"]]);
        }     
        return json_encode('New Recipe Added');
    }

    //get recipe by id
    function getRecipe($id){
        $recipe = Recipe::with('recipe_ingredients')->find($id);
        $recipe->direction = json_decode($recipe->direction);
        for($i=0;$i<count($recipe->recipe_ingredients);$i++){
            $recipe->recipe_ingredients[$i]->ingredient;
            $recipe->recipe_ingredients[$i]->unit;
        }
        return $recipe;
    }
    //search recipe by name
    function searchRecipe($searchTerm){
        return Recipe::whereRaw('LOWER(title) like ?', '%'.strtolower($searchTerm).'%')->get();
    }

    //filter Recipe by 
    function filterRecipe(Request $req){
        // $totals_table = DB::table('recipe_ingredients')
        //                 ->select('recipe_ingredients.recipe_id', DB::raw('COUNT(*) as total_ingredients'))
        //                 ->groupBy('recipe_ingredients.recipe_id');

        // $results = DB::table('recipes')
        //             ->join('recipe_ingredients', 'recipes.id', '=', 'recipe_ingredients.recipe_id')
        //             ->leftJoinSub($totals_table, 'totals', function(JoinClause $join){$join->on('recipe_ingredients.recipe_id', '=', 'totals.recipe_id');})
        //             ->select(DB::raw('COUNT(*) as matching_ingredients'), DB::raw('MAX(total_ingredients) as total_ingredients'), 'recipes.*')
        //             ->whereIn('recipe_ingredients.ingredient_id', array_map('intval',$req->ingredient_ids))
        //             ->where([
        //                 ['cooking_time_in_mins', '>=', intval($req->min_cook_time)],
        //                 ['cooking_time_in_mins', '<=', intval($req->max_cook_time)],
        //                 ['total_ingredients', '>=', intval($req->min_ingredients)],
        //                 ['total_ingredients', '<=', intval($req->max_ingredients)]])
        //             ->groupBy('recipes.id')
        //             ->orderByDesc('matching_ingredients')
        //             ->get();
        
        // return $results;
        // $matching_recipes = [];
        // foreach(RecipeIngredient::whereIn('ingredient_id', array_map('intval',$req->ingredient_ids))->select("recipe_id")->groupBy('recipe_id')->get() as $r){
        //     array_push($matching_recipes, $r->recipe_id);
        // }
        //return $matching_recipes;
        
        // $matching_recipes = array_map(function($result){return $result->recipe_id;},RecipeIngredient::whereIn('ingredient_id', array_map('intval',$req->ingredient_ids))->select("recipe_id")->groupBy('recipe_id')->get()->toArray()); 
        // return $matching_recipes;
        $matching_recipes = [];
        foreach (RecipeIngredient::whereIn('ingredient_id', array_map('intval', $req->ingredient_ids))->select("recipe_id", DB::raw('COUNT(*) as matching_ingredients'))->groupBy('recipe_id')->get() as $r) {
            array_push($matching_recipes, ["recipe_id"=>$r->recipe_id, "matching_ingredients"=>$r->matching_ingredients]);
        }
        $recipes = Recipe::whereIn('id', array_map(function($match){return $match["recipe_id"];}, $matching_recipes))->with('ingredients')->get();
        $return_recipes = [];
        foreach ($recipes as $recipe) {
            $recipe->matching_ingredients = 0;
            foreach($matching_recipes as $match){
                $recipe->matching_ingredients += $recipe->id===$match["recipe_id"] ? $match["matching_ingredients"] : 0; 
            }
            array_push($return_recipes , $recipe);
        }

        // sort by matching ingredients
        usort($return_recipes, function($a, $b){
            return $b["matching_ingredients"] - $a["matching_ingredients"];});

        
        return $return_recipes;
    }
    //get rating by recipe_id
    function getRatings(){
        
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
        $updateRecipe->save();
        return json_encode('recipe updated');
    }
    

    //get all measurements
    function getAllUnits(){
        return MeasurementUnit::all();
    }


    function getNumberOfSaves($id){
        return json_encode(['saves'=> count(SavedRecipe::where('recipe_id', $id)->get())]);
    }

    function getCommentsByRecipe($id){
       return Comment::where('recipe_id',$id)->where('parent_comment_id', null)->with('children')->get();
    }
    //add new comment
    function addComment(Request $req){
        $newComment = new Comment;
        $newComment->author_id = $req->author_id;
        $newComment->recipe_id = $req->recipe_id;
        $newComment->parent_comment_id =$req->parent_comment_id;
        $newComment->content = $req->content;
        $newComment->save();
        return json_encode('comment added');
    }
    //delete comment
    function deleteComment($id){
        Comment::find($id)->delete();
        return json_encode('comment deleted');
    }
    //update comment
    function updateComment(Request $req){
        $updateComment = Comment::find($req->id);
        $updateComment->content = $req->update_content;
        $updateComment->save();
        return json_encode('comment updated');
    }



    function test(){
        return Recipe::find(1)->recipe_ingredients->pluck('ingredient', 'ingredient_id', 'unit');
    }
}    
