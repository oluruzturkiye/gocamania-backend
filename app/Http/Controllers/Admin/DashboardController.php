<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Product;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_stores' => Store::count(),
            'total_products' => Product::count(),
            'total_listings' => Listing::count(),
            'pending_stores' => Store::where('is_approved', false)->count(),
            'pending_products' => Product::where('is_approved', false)->count(),
            'pending_listings' => Listing::where('is_approved', false)->count(),
        ];

        return response()->json($stats);
    }

    public function pendingApprovals()
    {
        $data = [
            'stores' => Store::with('user')
                ->where('is_approved', false)
                ->latest()
                ->take(5)
                ->get(),
            'products' => Product::with(['store', 'store.user'])
                ->where('is_approved', false)
                ->latest()
                ->take(5)
                ->get(),
            'listings' => Listing::with('user')
                ->where('is_approved', false)
                ->latest()
                ->take(5)
                ->get(),
        ];

        return response()->json($data);
    }
}
