<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\Product\CreateRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('id', 'desc')->paginate(10);

        return response()->json([
            'message' => 'get list product successfully',
            'data' => ProductResource::collection($products)
        ]);

        // $products = Product::all();
        // // dd($products);

        // return response()->json([
        //     'message' => 'get list product successfully',
        //     'data' => $products
        // ]);
    }

    public function store(CreateRequest $request)
    {
        $input = $request->validated();

        $product = Product::create([
            'name' => $input['name'],
            'price' => $input['price'],
            'description' => $input['description']
        ]);

        return response()->json([
            'message' => 'create product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    public function show(Product $product)
    {
        return response()->json([
            'message' => 'get product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'delete product successfully'
        ]);
    }

    public function update(Product $product, CreateRequest $request)
    {
        $input = $request->validated();

        $product->update([
            'name' => $input['name'],
            'price' => $input['price'],
            'description' => $input['description']
        ]);

        return response()->json([
            'message' => 'Change product successfull',
            'data' => new ProductResource($product)
        ]);
    }
}
