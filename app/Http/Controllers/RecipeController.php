<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\MeasurementUnit;
use App\Models\SavedRecipe;
use Illuminate\Database\Query\JoinClause;

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
        $totals_table = DB::table('recipe_ingredients')
                        ->select('recipe_ingredients.recipe_id', DB::raw('COUNT(*) as total_ingredients'))
                        ->groupBy('recipe_ingredients.recipe_id');

        $results = DB::table('recipes')
                    ->join('recipe_ingredients', 'recipes.id', '=', 'recipe_ingredients.recipe_id')
                    ->leftJoinSub($totals_table, 'totals', function(JoinClause $join){$join->on('recipe_ingredients.recipe_id', '=', 'totals.recipe_id');})
                    ->select(DB::raw('COUNT(*) as matching_ingredients'), DB::raw('MAX(total_ingredients) as total_ingredients'), 'recipes.*')
                    ->whereIn('recipe_ingredients.ingredient_id', $req->ingredient_ids)
                    ->where([
                        ['cooking_time_in_mins', '>=', $req->min_cook_time],
                        ['cooking_time_in_mins', '<=', $req->max_cook_time],
                        ['total_ingredients', '>=', $req->min_ingredients],
                        ['total_ingredients', '<=', $req->max_ingredients]])
                    ->groupBy('recipes.id')
                    ->orderByDesc('matching_ingredients')
                    ->get();

        //  DB::select('select count(*) as matching_ingredients, max(total_ingredients) as total_ingredients, r.* 
        //                         from capstone.recipes r 
        //                         join capstone.recipe_ingredients ri 
        //                             on r.id = ri.recipe_id 
        //                         left join (select ri2.recipe_id, count(*) as total_ingredients from capstone.recipe_ingredients ri2 group by ri2.recipe_id) as totals 
        //                             on totals.recipe_id = ri.recipe_id 
        //                         where ri.ingredient_id in ? 
        //                             and total_ingredients >= ? 
        //                             and total_ingredients <= ? 
        //                             and cooking_time_in_mins >= ? 
        //                             and cooking_time_in_mins <= ? 
        //                         group by r.id 
        //                         order by matching_ingredients desc;',
        //                         [json_encode($req->ingredient_ids), $req->min_ingredients, $req->max_ingredients,  $req->min_cook_time, $req->max_cook_time]);
        return $results;

        // $recipe_matches = RecipeIngredient::select('recipe_id',DB::raw("count(*) as total_matches"))->whereIn('ingredient_id', $req->ingredient_ids)->groupBy('recipe_id')->orderByDesc('total_matches')->get();
        // $matches = $recipe_matches->pluck('recipe')-where;
        // return $matches;
        // foreach($recipe_matches as $match){
        //      array_push($matches, $match->recipe_id);
        //  }
        // $recipes =  Recipe::whereIn('id', $matches)->where([['cooking_time_in_mins', '>=', $req->min_cook_time], ['cooking_time_in_mins', '<=', $req->max_cook_time],[],[]])->get();
        // //$ingre_query = RecipeIngredient::select('recipe_id',DB::raw("count(*) as total_ingredients"))->groupBy('recipe_id')->get() ;
        
        // //return $ingre_query;


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
