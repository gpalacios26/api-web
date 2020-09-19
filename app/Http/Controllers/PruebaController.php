<?php

namespace App\Http\Controllers;
use App\Models\Post;
use App\Models\Category;

use Illuminate\Http\Request;

class PruebaController extends Controller
{
    public function testOrmCategory(){
        $categories = Category::all();
        $data = array(
            'status' => 'success',
            'code' => 200,
            'cars' => $categories
        );

        return response()->json($data, 200);
    }

    public function testOrmPost(){
        $posts = Post::all();
        $data = array(
            'status' => 'success',
            'code' => 200,
            'cars' => $posts
        );

        return response()->json($data, 200);
    }
}
