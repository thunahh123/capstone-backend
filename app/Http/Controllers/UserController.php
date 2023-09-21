<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    //get all users
    function getAllUsers(){
        return User::all();
    }

    //get user by id
    function getUser($id){
        return User::find($id);
        
    }

    //delete user by id
    function deleteUser($id){
        User::find($id)->delete();
        return json_encode('item deleted');
        
    }

    //update email 
    function updateEmail(Request $req){
        $updateUser = User::find($req->id);
        $updateUser->email = $req->newemail;
        $updateUser->save();
        return json_encode('email updated');
    }
    //update new pw
    function updatePassword(Request $req){
        $updateUser = User::find($req->id);
        $updateUser->pw = $req->newpw;
        $updateUser->save();
        return json_encode('password updated');
    }
    //register newuser
    function register(Request $req){
        $newUser = new User;
        $newUser->name = $req->username;
        $newUser->pw = $req->password;
        $newUser->email = $req->email;
        $newUser->save();
        return json_encode('new user added');
    }
    //login
    function login(Request $req){
        $user = User::firstWhere('name',$req->username);
        if(!$user || $req->password !== $user->pw){
            return json_encode('Invalid');
        }  
        else{
            return json_encode('logged in');
        }        
    }   
    
    

    
}
