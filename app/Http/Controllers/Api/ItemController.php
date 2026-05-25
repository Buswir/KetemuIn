<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Item::with('user');

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $items = $query->latest()->paginate(10);

            return ItemResource::collection($items);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve items.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemRequest $request)
    {
        try {
            $validated = $request->validated();
            
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('items', 'public');
                $validated['image_url'] = $path;
            }

            $validated['user_id'] = auth()->id();
            $item = Item::create($validated);

            return new ItemResource($item->load('user'));
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $item = Item::with('user')->find($id);

            if (!$item) {
                return response()->json(['message' => 'Item not found.'], 404);
            }

            return new ItemResource($item);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemRequest $request, string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                return response()->json(['message' => 'Item not found.'], 404);
            }

            if ($item->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $validated = $request->validated();

            if ($request->hasFile('image')) {
                // Delete old image
                if ($item->image_url) {
                    Storage::disk('public')->delete($item->image_url);
                }
                
                $path = $request->file('image')->store('items', 'public');
                $validated['image_url'] = $path;
            }

            $item->update($validated);

            return new ItemResource($item->load('user'));
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                return response()->json(['message' => 'Item not found.'], 404);
            }

            if ($item->user_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            if ($item->image_url) {
                Storage::disk('public')->delete($item->image_url);
            }

            $item->delete();

            return response()->json(['message' => 'Item deleted successfully.'], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
