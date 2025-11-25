<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ApiProductController extends Controller
{
    public function getProduct($product_name){
        $products = Product::where('name', 'like', '%' . $product_name . '%')->get();
        return response()->json(['products' => $products]);
    }
}
