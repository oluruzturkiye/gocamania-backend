<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Listing::with('user');

        // Filtreleme seçenekleri
        if ($request->has('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        $listings = $query->latest()->paginate(10);

        return response()->json($listings);
    }

    public function show(Listing $listing)
    {
        $listing->load('user');
        return response()->json($listing);
    }

    public function approve(Listing $listing)
    {
        $listing->update(['is_approved' => true]);

        // İlan sahibine bildirim gönderme işlemi burada yapılabilir

        return response()->json([
            'message' => 'İlan başarıyla onaylandı',
            'listing' => $listing
        ]);
    }

    public function reject(Request $request, Listing $listing)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $listing->update([
            'is_approved' => false,
            'rejection_reason' => $validated['rejection_reason']
        ]);

        // İlan sahibine red nedeni ile birlikte bildirim gönderme işlemi burada yapılabilir

        return response()->json([
            'message' => 'İlan reddedildi',
            'listing' => $listing
        ]);
    }

    public function update(Request $request, Listing $listing)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'condition' => 'required|in:new,used',
            'is_active' => 'boolean'
        ]);

        $listing->update($validated);

        return response()->json([
            'message' => 'İlan bilgileri güncellendi',
            'listing' => $listing
        ]);
    }

    public function destroy(Listing $listing)
    {
        // İlan resimlerini silme işlemi burada yapılacak
        $listing->delete();

        return response()->json([
            'message' => 'İlan başarıyla silindi'
        ]);
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'listing_ids' => 'required|array',
            'listing_ids.*' => 'exists:listings,id'
        ]);

        Listing::whereIn('id', $validated['listing_ids'])
            ->update(['is_approved' => true]);

        return response()->json([
            'message' => 'Seçili ilanlar başarıyla onaylandı'
        ]);
    }

    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'listing_ids' => 'required|array',
            'listing_ids.*' => 'exists:listings,id',
            'rejection_reason' => 'required|string|max:500'
        ]);

        Listing::whereIn('id', $validated['listing_ids'])
            ->update([
                'is_approved' => false,
                'rejection_reason' => $validated['rejection_reason']
            ]);

        return response()->json([
            'message' => 'Seçili ilanlar reddedildi'
        ]);
    }

    public function statistics()
    {
        $stats = [
            'total' => Listing::count(),
            'approved' => Listing::where('is_approved', true)->count(),
            'pending' => Listing::where('is_approved', false)->count(),
            'new_condition' => Listing::where('condition', 'new')->count(),
            'used_condition' => Listing::where('condition', 'used')->count(),
            'active' => Listing::where('is_active', true)->count(),
            'inactive' => Listing::where('is_active', false)->count(),
        ];

        return response()->json($stats);
    }
}
