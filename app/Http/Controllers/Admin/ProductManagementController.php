<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductManagementController extends Controller
{
    public function index()
    {
        $products = Product::with(['store', 'store.user'])
            ->latest()
            ->paginate(10);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load(['store', 'store.user']);
        return response()->json($product);
    }

    public function approve(Product $product)
    {
        $product->update(['is_approved' => true]);

        return response()->json([
            'message' => 'Ürün başarıyla onaylandı',
            'product' => $product
        ]);
    }

    public function reject(Product $product)
    {
        $product->update(['is_approved' => false]);

        return response()->json([
            'message' => 'Ürün reddedildi',
            'product' => $product
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Ürün bilgileri güncellendi',
            'product' => $product
        ]);
    }

    public function destroy(Product $product)
    {
        // Ürün resimlerini silme işlemi burada yapılacak
        $product->delete();

        return response()->json([
            'message' => 'Ürün başarıyla silindi'
        ]);
    }
}
