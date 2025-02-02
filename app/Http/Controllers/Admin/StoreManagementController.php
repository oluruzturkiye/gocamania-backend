<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreManagementController extends Controller
{
    public function index()
    {
        $stores = Store::with('user')
            ->latest()
            ->paginate(10);

        return response()->json($stores);
    }

    public function show(Store $store)
    {
        $store->load(['user', 'products']);
        return response()->json($store);
    }

    public function approve(Store $store)
    {
        $store->update(['is_approved' => true]);
        
        // Mağaza sahibinin rolünü güncelle
        $store->user->update(['role' => 'store']);

        return response()->json([
            'message' => 'Mağaza başarıyla onaylandı',
            'store' => $store
        ]);
    }

    public function reject(Store $store)
    {
        $store->update(['is_approved' => false]);
        
        // Mağaza sahibinin rolünü normal kullanıcı olarak güncelle
        $store->user->update(['role' => 'user']);

        return response()->json([
            'message' => 'Mağaza reddedildi',
            'store' => $store
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'whatsapp_number' => 'required|string|max:20',
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Mağaza bilgileri güncellendi',
            'store' => $store
        ]);
    }

    public function destroy(Store $store)
    {
        // Mağazayı ve ilişkili ürünleri sil
        $store->products()->delete();
        $store->delete();

        // Kullanıcı rolünü normal kullanıcı olarak güncelle
        $store->user->update(['role' => 'user']);

        return response()->json([
            'message' => 'Mağaza ve ilişkili tüm ürünler silindi'
        ]);
    }
}
