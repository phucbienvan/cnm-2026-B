<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
        ]);

        $product = Product::create($validatedData);

        return response()->json([
            'message' => 'Thêm sản phẩm thành công',
            'data' => $product->refresh(),
        ], 201);
    }

    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'data' => $products,
        ]);
    }

    public function show($id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }

        return response()->json([
            'data' => $product,
        ]);
    }

    public function update (Request $request, $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0',
        ]);

        $product->update($validatedData);

        return response()->json([
            'message' => 'Cập nhật sản phẩm thành công',
            'data' => $product->refresh(),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Xóa sản phẩm thành công',
        ]);
    }
}
