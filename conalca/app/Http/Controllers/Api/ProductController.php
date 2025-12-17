<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('producto_nombre')->get([
            'producto_codigo',
            'producto_nombre',
        ]);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
