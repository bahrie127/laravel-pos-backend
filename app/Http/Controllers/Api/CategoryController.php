<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    //index
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'status' => true,
            'message' => 'List data categories',
            'data' => $categories
        ]);
    }
}
