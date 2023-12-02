<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\MeasurementUnit;
use App\Models\Rating;
use App\Models\SavedRecipe;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\QueryException;

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
        foreach($req->ingredients as $ingredient){
            DB::statement("INSERT INTO recipe_ingredients(recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)", 
                [$newRecipe->id,
                 $ingredient["id"], 
                 $ingredient["quantity"], 
                 $ingredient["unit_id"]]);
        }     
        return json_encode(['status'=> 'success','message'=>"New recipe added",'newId'=>$newRecipe->id]);
    }
    //get all users
    function getAllRecipes()
    {
        return Recipe::with('saved_recipes','comments', 'user')->get();
    }

    //get recipe by id
    function getRecipe($id){
        $recipe = Recipe::with('recipe_ingredients')->with('user', 'ratings')->find($id);
        if(!$recipe){
            return json_encode(['status'=> 'fail','message'=>"Recipe could not be found",'content'=>[]]);
        }
        $recipe->views++;
        $recipe->save();
        $recipe->direction = json_decode($recipe->direction);
        for($i=0;$i<count($recipe->recipe_ingredients);$i++){
            $recipe->recipe_ingredients[$i]->ingredient;
            $recipe->recipe_ingredients[$i]->unit;
        }
        $saved = [];
        foreach(SavedRecipe::where([["recipe_id",'=',$id]])->select('user_id')->get() as $save){
            array_push($saved, $save->user_id);
        }
        $recipe->saved = $saved;
        $total=0;
        foreach($recipe->ratings as $r){
            $total+=$r->score;
        }
        $recipe->avg_rating = $total/max(1,count($recipe->ratings));
        return json_encode(['status'=> 'success','message'=>"Recipe found",'content'=>$recipe]);

    }
    //search recipe by name
    function searchRecipe($searchTerm){
        return Recipe::whereRaw('LOWER(title) like ?', '%'.strtolower($searchTerm).'%')->get();
    }

    function rateRecipe(Request $req){
        $session = Session::firstWhere("session_key", $req->session_key);
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }

        $user = User::find($session->user_id);
        if(!$user){
            return json_encode(['status'=> 'fail','message'=>"Couldn't find user"]);
        }

        $rating = Rating::firstWhere([["recipe_id",'=',$req->recipe_id], ['user_id', '=', $session->user_id]]); 
        $message = "Rating successfully updated";
        if(!$rating){
            $rating = new Rating;
            $rating->recipe_id = $req->recipe_id;
            $rating->user_id = $session->user_id;
            $message = "Rating successfully added";
        }
        if($req->score == -1){
            $rating->delete();
            return json_encode(['status'=> 'success','message'=>"Rating successfully deleted"]);
        }
        $rating->score = $req->score;
        $rating->save();
        return json_encode(['status'=> 'success','message'=>$message]);
    }

    //filter Recipe by 
    function filterRecipe(Request $req){
    
        $matching_recipes = [];
        foreach (RecipeIngredient::whereIn('ingredient_id', array_map('intval', $req->ingredient_ids))->select("recipe_id", DB::raw('COUNT(*) as matching_ingredients'))->groupBy('recipe_id')->get() as $r) {
            array_push($matching_recipes, ["recipe_id"=>$r->recipe_id, "matching_ingredients"=>$r->matching_ingredients]);
        }


        $recipes = Recipe::whereIn('id', array_map(function($match){return $match["recipe_id"];}, $matching_recipes))
                        ->where([['cooking_time_in_mins', '>=', intval($req->min_cook_time)], 
                            ['cooking_time_in_mins', '<=', intval($req->max_cook_time)],])
                        ->with('ingredients', 'ratings', 'user')->get();

        $return_recipes = [];
        foreach ($recipes as $recipe) {
            $recipe->matching_ingredients = 0;
            foreach($matching_recipes as $match){
                $recipe->matching_ingredients += $recipe->id===$match["recipe_id"] ? $match["matching_ingredients"] : 0; 
            }
            array_push($return_recipes , $recipe);
        }

        $filter_ingredients = fn($i) => (count($i->ingredients)<=$req->max_ingredients && count($i->ingredients)>=$req->min_ingredients);
        $return_recipes = array_filter($return_recipes, $filter_ingredients);

        // sort by matching ingredients
        usort($return_recipes, function($a, $b){
            return $b["matching_ingredients"] - $a["matching_ingredients"];});
        foreach($return_recipes as $rr){
             $total=0;
            foreach($rr->ratings as $r){
                $total+=$r->score;
            }
            $rr->avg_rating = $total/max(1,count($rr->ratings));
        }
           
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
        $session = Session::firstWhere("session_key","=",$req->session_key);  
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }

        $user = User::find($session->user_id);
        if(!$user){
            return json_encode(['status'=> 'fail','message'=>"Couldn't find user"]);
        }

        $updateRecipe = Recipe::find($req->recipe_id); 
        if(!$updateRecipe){
            return json_encode(['status'=> 'fail','message'=>"Couldn't find recipe"]);
        }
        if(!$user->is_admin && $updateRecipe->author_id!==$user->id){
            return json_encode(['status'=> 'fail','message'=>"Not authorized"]);
        } 
        $updateRecipe->cooking_time_in_mins = $req->new_cooking_time;
        $updateRecipe->title = $req->new_title;
        $updateRecipe->serving = $req->new_serving;
        $updateRecipe->description = $req->new_desc;
        $updateRecipe->direction = json_encode($req->new_direction); 
        $updateRecipe->save();
        RecipeIngredient::where("recipe_id", $req->recipe_id)->delete();
        foreach($req->new_ingredients as $ingredient){
            DB::statement("INSERT INTO recipe_ingredients(recipe_id, ingredient_id, quantity, unit_id) VALUES (?, ?, ?, ?)", 
                [$req->recipe_id,
                 $ingredient["id"], 
                 $ingredient["quantity"], 
                 $ingredient["unit_id"]]);
        }   
        return json_encode(['status'=> 'success','message'=>'Recipe updated successfully']);

    }
    

    //get all measurements
    function getAllUnits(){
        return MeasurementUnit::all();
    }


    
    //get all comments of a recipe
    function getCommentsByRecipe($id){
       return Comment::where('recipe_id',$id)->whereNull('parent_comment_id')->with('user')->with('children')->with('children.user')->get();
    }
    //add new comment
    function addComment(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }
        $newComment = new Comment;
        $newComment->author_id = $session->user_id;
        $newComment->recipe_id = $req->recipe_id;
        $newComment->parent_comment_id =$req->parent_comment_id;
        $newComment->content = $req->content;
        try{
            $newComment->save();
            return json_encode(['status'=> 'success','message'=>"Comment added"]);
        }
        catch(QueryException $ex){
            return json_encode(['status'=> 'fail','message'=>"Couldn't add comment at this time."]);
        }
        
    }
    
    //delete comment
    function deleteComment(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }
        $user = User::find($session->user_id);
        $comment = Comment::find($req->comment_id);
        if(!$user->is_admin && $comment->author_id!==$user->id){
            return json_encode(['status'=> 'fail','message'=>"Not authorized"]);
        }
        $comment->delete();
        return json_encode(['status'=> 'success','message'=>"Comment deleted"]);
    }
    //update comment
    function updateComment(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }
        $user = User::find($session->user_id);
        if(!$user){
            return json_encode(['status'=> 'fail','message'=>"Couldn't find user"]);
        }
        $updateComment = Comment::find($req->id);
        if(!$updateComment){
            return json_encode(['status'=> 'fail','message'=>"Couldn't find comment"]);
        }
        if(!$user->is_admin && $updateComment->author_id!==$user->id){
            return json_encode(['status'=> 'fail','message'=>"Not authorized"]);
        }
        if(!$req->update_content){
            return json_encode(['status'=> 'fail','message'=>"New content cannot be empty"]);
        }
        $updateComment->content = $req->update_content;
        $updateComment->save();
        return json_encode(['status'=> 'success','message'=>"Comment updated"]);
    }

    //get feature recipes
    function getFeaturedRecipes(){
        return Recipe::where('featured',true)->get();
    }

    //set recipe as a feature
    function setFeaturedRecipe(Request $req){         
        $session = Session::firstWhere("session_key","=",$req->session_key);  
        if(!$session){
            return json_encode(['status'=> 'fail','message'=>"Please login"]);
        }
        $user = User::find($session->user_id);
        if(!$user->is_admin){
            return json_encode(['status'=> 'fail','message'=>"Not authorized"]);
        }
        $recipe = Recipe::find($req->recipe_id);
        $recipe->featured = !$recipe->featured;
        $recipe->save();
        return json_encode(['status'=> 'success','message'=>"Recipe is now".(!$recipe->featured ? " not " : " ")."featured."]);
    }


    function test(){
        return Recipe::find(1)->recipe_ingredients->pluck('ingredient', 'ingredient_id', 'unit');
    }
}    
