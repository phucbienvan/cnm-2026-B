<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\CreateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('user')->orderBy('id', 'desc')->paginate(10);

        return response()->json([
            'message' => 'get list product successfully',
            'data' => ProductResource::collection($products)
        ]);
    }

    public function store(CreateRequest $request)
    {
        $input = $request->validated();
        $user = auth()->user();

        $product = Product::create([
            'name' => $input['name'],
            'price' => $input['price'],
            'description' => $input['description'],
            'user_id' => $user->id
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

    public function update(CreateRequest $request, Product $product)
    {
        $user = auth()->user();

        if ($product->user_id !== $user->id) {
            return response()->json([
                'message' => 'you are not authorized to update this product'
            ], 403);
        }

        $input = $request->validated();

        $product->update([
            'name' => $input['name'],
            'price' => $input['price'],
            'description' => $input['description']
        ]);

        return response()->json([
            'message' => 'update product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    public function destroy(Product $product)
    {
        $user = auth()->user();

        if ($product->user_id !== $user->id) {
            return response()->json([
                'message' => 'Product didnt created by you!'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'delete product successfully'
        ]);
    }
}
