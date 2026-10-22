<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\Product\CreateRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the products (Read all).
     */
    public function index()
    {
        $products = Product::orderBy('id', 'desc')->paginate(10);

        return response()->json([
            'message' => 'get list product successfully',
            'data' => ProductResource::collection($products)
        ]);
    }

    /**
     * Store a newly created product in storage (Create).
     */
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
        ], 201);
    }

    /**
     * Display the specified product (Read one).
     */
    public function show(Product $product)
    {
        return response()->json([
            'message' => 'get product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Update the specified product in storage (Update).
     */
    public function update(UpdateRequest $request, Product $product)
    {
        $input = $request->validated();
        $product->update($input);

        return response()->json([
            'message' => 'update product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Remove the specified product from storage (Delete).
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'delete product successfully'
        ]);
    }
}
