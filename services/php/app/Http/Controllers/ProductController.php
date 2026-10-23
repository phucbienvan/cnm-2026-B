<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\Product\CreateRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Lấy danh sách tất cả sản phẩm (phân trang, mới nhất lên đầu).
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
     * Tạo sản phẩm mới.
     * Gán user_id = người dùng đang đăng nhập (chủ sở hữu sản phẩm).
     */
    public function store(CreateRequest $request)
    {
        $input = $request->validated();

        // Lấy ID của user đang đăng nhập để gán làm chủ sở hữu sản phẩm
        $product = Product::create([
            'name'        => $input['name'],
            'price'       => $input['price'],
            'description' => $input['description'],
            'user_id'     => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'create product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Lấy chi tiết một sản phẩm theo ID.
     */
    public function show(Product $product)
    {
        return response()->json([
            'message' => 'get product successfully',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Xóa sản phẩm.
     * Kiểm tra xem user đang đăng nhập có phải chủ sở hữu sản phẩm không.
     * Nếu không phải → trả về lỗi 403 Forbidden.
     */
    public function destroy(Request $request, Product $product)
    {
        // Lấy thông tin user đang đăng nhập
        $currentUser = $request->user();

        // Kiểm tra chính chủ: user_id của sản phẩm phải khớp với user đang đăng nhập
        if ($product->user_id !== $currentUser->id) {
            return response()->json([
                'message' => 'Forbidden: you are not the owner of this product'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'delete product successfully'
        ]);
    }

    /**
     * Cập nhật thông tin sản phẩm.
     */
    public function update(CreateRequest $request, Product $product)
    {
        $input = $request->validated();

        $product->update([
            'name'        => $input['name'],
            'price'       => $input['price'],
            'description' => $input['description'],
        ]);

        return response()->json([
            'message' => 'update product successfully',
            'data' => new ProductResource($product)
        ]);
    }
}
