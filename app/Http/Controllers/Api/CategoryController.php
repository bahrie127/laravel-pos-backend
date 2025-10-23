<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //index
    public function index()
    {
        $categories = \App\Models\Category::all();
        return response()->json([
            'success' => true,
            'message' => 'List Data Category',
            'data' => $categories
        ], 200);
    }
}
