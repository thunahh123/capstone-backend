<?php

namespace App\Http\Controllers;

use App\Models\SavedRecipe;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Comment;
use App\Models\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    //get all users
    function getAllUsers(){
        return User::all();
    }

    //get user by id
    function getUser($id){
        return User::find($id); //id = 'savedRecipes'
    }

    //delete user by id
    function deleteUser(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key); 
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        User::find($session->user_id)->delete();
        return json_encode('item deleted');
        
    }

    //update email 
    function updateEmail(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key); 
        $updateUser = User::find($req->id);
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        if($session->user_id !== $updateUser->id){
            return json_encode(['status'=> 'Failed','messages'=>"You cannot update this email"]);
        }  
        $updateUser->email = $req->newemail;
        $updateUser->save();
        return json_encode('Email updated');
    }
    //update new pw
    function updatePassword(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key); 
        $updateUser = User::find($req->id);
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        if($session->user_id !== $updateUser->id){
            return json_encode(['status'=> 'Failed','messages'=>"You cannot update this password"]);
        }        
        $updateUser->pw = Hash::make($req->newpw);
        $updateUser->save();
        return json_encode('password updated');
    }
    //register newuser
    function register(Request $req){
        $newUser = new User;
        $newUser->name = $req->username;
        $newUser->pw = Hash::make($req->password);
        $newUser->email = $req->email;
        $newUser->save();
        return json_encode('new user added');
    }
    //login
    function login(Request $req){
        $user = User::firstWhere('name',$req->username);
        if(!$user){
            return json_encode('Wrong username');
        }
        else if(!Hash::check($req->pw, $user->pw)){
            return json_encode('Wrong password');
        }  
        else{
            $newSession = new Session;
            $newSession->user_id = $user->id;
            $newSession->session_key = Str::uuid();
            $newSession->save();            
            return json_encode(['status' => 'Success', 'session_key' => $newSession->session_key]);
        }        
    } 

    //logout
    function logout(Request $req){
        $session = Session::firstWhere('session_key','=',$req->session_key);
        if($session){
            $session->delete();
        }
        return json_encode(['status'=>'Success', 'message'=>'Logged out']);

    }
    
    //get saved recipe by user id
    function getSavedRecipes(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        $savedRecipe = SavedRecipe::where('user_id','=',$session->user_id);
        return $savedRecipe->get()->pluck('recipe');       
        
    }

    //add saved recipe
    function addSavedRecipe(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key); 
        if(!$session){
            return json_encode(['status'=> 'Failed','message'=>"Please login"]);
        }
        $newSavedRecipe = new SavedRecipe;
        $newSavedRecipe->user_id = $session->user_id;
        $newSavedRecipe->recipe_id = $req->recipe_id;
        $newSavedRecipe->save();
        return json_encode(['status'=> 'Success']);
    }

    //remove saved recipe
    function deleteSavedRecipe(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        SavedRecipe::where('user_id',$session->user_id)->where('recipe_id',$req->recipe_id)->delete();
        return json_encode('item deleted');
        
    }

    function getCommentsByUser(Request $req){
        $session = Session::firstWhere("session_key","=",$req->session_key);
        echo $session;
        if(!$session){
            return json_encode(['status'=> 'Failed','messages'=>"Please login"]);
        }
        return Comment::where('author_id',"=",$session->user_id)->get();
         
    }
}
