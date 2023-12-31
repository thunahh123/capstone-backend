<?php

namespace App\Http\Controllers;

use App\Models\SavedRecipe;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Comment;
use App\Models\Session;
use App\Models\Recipe;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(Request $req)
    {
        Log::debug("User Controller", ["Request" => $req, "Body" => $req->all()]);
    }

    //get all users
    function getAllUsers()
    {
        $allUsers = User::with('recipes', 'comments')->get();
        return $allUsers;
    }

    //get user by id
    function getUser($id)
    {
        $user = User::find($id); //id = 'savedRecipes'
        if(!$user){
            return json_encode(['status' => 'fail', 'message' => "Could not find user"]);
        }
        return json_encode(['status' => 'success', 'message' => 'User found', 'content' => $user]);
    }

    //get user by username
    function getUserByName($name)
    {
        $user = User::firstWhere('name', '=', $name);
        if(!$user){
            return json_encode(['status' => 'fail', 'message' => "Could not find user"]);
        }
        return json_encode(['status' => 'success', 'message' => 'User found', 'content' => $user]);
    }

    //delete user by id
    function deleteUser(Request $req)
    {
        $session = Session::firstWhere("session_key", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        Log::debug("del user", ['user_id'=>$req->user_id,'session'=>$session, "is_admin"=> $session->user->is_admin]);
        if ($session->user_id !== intval($req->user_id) && !$session->user->is_admin) {
            return json_encode(['status' => 'fail', 'message' => "Unauthorized action"]);
        }
        $user = User::find($req->user_id);
        if (!$user) {
            return json_encode(['status' => 'fail', 'message' => "Could not find user"]);
        }
        
        if($user->is_admin){
            return json_encode(['status' => 'fail', 'message' => "User is an admin"]);
        }
        $user->delete();
        return json_encode(['status' => 'success', 'message' => 'User deleted']);
    }

    //turn to an admin
    function promoteAdmin(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        if (!$session->user->is_admin) {
            return json_encode(['status' => 'fail', 'message' => "Unauthorized action"]);
        }
        $user = User::find($req->user_id);
        if (!$user) {
            return json_encode(['status' => 'fail', 'message' => "Could not find user"]);
        }
        $user->is_admin = true;
        $user->save();
        return json_encode(['status' => 'success', 'message' => 'Sucessfully updated']);
    }


    //update email 
    function updateEmail(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        if (User::firstWhere("email", $req->newemail)) {
            return json_encode(['status' => 'fail', 'message' => 'Email already in use']);
        }
        $updateUser = User::find($session->user_id);
        if (!$updateUser) {
            return json_encode(['status' => 'fail', 'message' => 'Wrong credentials']);
        }
        if (!Hash::check($req->password, $updateUser->pw)) {
            return json_encode(['status' => 'fail', 'message' => 'Wrong credentials 2']);
        } else {
            // if($session->user_id !== $updateUser->id){
            //     return json_encode(['status'=> 'fail','message'=>"You cannot update this email"]);
            // }
            $updateUser->email = $req->newemail;
            $updateUser->save();
            return json_encode(['status' => 'success', 'message' => 'Email updated']);
        }
    }
    //update new pw
    function updatePassword(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        $updateUser = User::find($session->user_id);
        if (!$updateUser) {
            return json_encode(['status' => 'fail', 'message' => 'Wrong credentials']);
        }
        if (!Hash::check($req->password, $updateUser->pw)) {
            return json_encode(['status' => 'fail', 'message' => 'You cannot update this password']);
        } else {
            $updateUser->pw = Hash::make($req->newpw);
            $updateUser->save();
            return json_encode(['status' => 'success', 'message' => 'Password successfully updated']);
        }
    }
    //register newuser
    function register(Request $req)
    {
        if (count(User::where("email", $req->email)->get()) > 0) {
            return json_encode(['status' => 'fail', 'message' => "This email already in use"]);
        }
        if (count(User::where("name", $req->username)->get()) > 0) {
            return json_encode(['status' => 'fail', 'message' => "This username already in use"]);
        }
        $newUser = new User;
        $newUser->name = $req->username;
        $newUser->pw = Hash::make($req->password);
        $newUser->email = $req->email;
        $newUser->save();
        return json_encode(['status' => 'success', 'message' => 'Account created successfully']);
    }
    //login
    function login(Request $req)
    {
        $user = User::firstWhere('name', $req->username);
        if (!$user) {
            return json_encode(['status' => 'fail', 'message' => 'Wrong credentials']);
        } else if (!Hash::check($req->pw, $user->pw)) {
            return json_encode(['status' => 'fail', 'message' => 'Wrong credentials']);
        } else {
            $user->last_login = date(DATE_ATOM, strtotime('now'));
            $user->save();
            $oldSession = Session::where("user_id", "=", $user->id)->get();
            foreach ($oldSession as $s) {
                $s->delete();
            }
            $newSession = new Session;
            $newSession->user_id = $user->id;
            $newSession->session_key = Str::uuid();
            $newSession->save();
            return json_encode(['status' => 'success','user_id'=>$newSession->user_id, 'session_key' => $newSession->session_key, 'is_admin' => $user->is_admin]);
        }
    }

    //logout
    function logout(Request $req)
    {
        $session = Session::firstWhere('session_key', '=', $req->session_key);
        if ($session) {
            $session->delete();
        }
        return json_encode(['status' => 'success', 'message' => 'Logged out']);
    }

    //get saved recipe by user id
    function getSavedRecipes(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        // return $session;
        // if (!$session) {
        //     return json_encode(['status' => 'fail', 'message' => "Please login"]);
        // }
        $savedRecipe = SavedRecipe::where('user_id', '=', $req->id);
        return $savedRecipe->get()->pluck('recipe');
    }



    //add saved recipe
    function addSavedRecipe(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        $newSavedRecipe = new SavedRecipe;
        $newSavedRecipe->user_id = $session->user_id;
        $newSavedRecipe->recipe_id = $req->recipe_id;
        $newSavedRecipe->save();
        return json_encode(['status' => 'success', 'message' => "Successfully saved"]);
    }

    //remove saved recipe
    function deleteSavedRecipe(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        SavedRecipe::where('user_id', $session->user_id)->where('recipe_id', $req->recipe_id)->delete();
        return json_encode(['status' => 'success', 'message' => 'item deleted']);
    }
    //get recipes created by a user
    function getCreatedRecipes(Request $req)
    {
        // $session = Session::firstWhere("session_key", "=", $req->session_key);
        // if (!$session) {
        //     return json_encode(['status' => 'fail', 'message' => "Please login"]);
        // }
        $recipes = Recipe::where('author_id', '=', $req->id);
        return $recipes->get();
    }
    //remove recipes created by a user
    function deleteMyRecipe(Request $req)
    {
        $session = Session::with('user')->firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        $recipe = Recipe::firstWhere('id', $req->recipe_id);
        if($recipe->author_id !== $session->user_id && !$session->user->is_admin){
            return json_encode(['status' => 'fail', 'message' => "Not authorized"]);
        }
        $recipe->delete();
        return json_encode(['status' => 'success', 'message' => 'item deleted']);
    }

    //get comments by user
    function getCommentsByUser(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        // if (!$session) {
        //     return json_encode(['status' => 'fail', 'message' => "Please login"]);
        // }
        return Comment::where('author_id', "=", $req->id)->with('recipe')->get();
    }

    //delete comment created by user
    function deleteMyComment(Request $req)
    {
        $session = Session::firstWhere("session_key", "=", $req->session_key);
        if (!$session) {
            return json_encode(['status' => 'fail', 'message' => "Please login"]);
        }
        Comment::where('author_id', "=", $session->user_id)->where('id', $req->comment_id)->delete();
        return json_encode(['status' => 'success', 'message' => 'item deleted']);
    }

    //a user like a comment
    // function likeComment(Request $req){
    //     $session = Session::firstWhere("session_key","=",$req->session_key);
    //     if(!$session){
    //         return json_encode(['status'=> 'fail','message'=>"Please login"]);
    //     }
    // }
}
