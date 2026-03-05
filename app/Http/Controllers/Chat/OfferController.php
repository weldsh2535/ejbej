<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Conversation;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller
{
    public function store(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        // Check if user is part of conversation
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized',
                'errors' => ['permission' => ['You do not have access to this conversation']]
            ], 403);
        }

        $request->validate([
            'offered_price' => 'required|numeric|min:0.01',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Check for pending offer
        $pendingOffer = $conversation->offers()
            ->where('sender_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($pendingOffer) {
            return response()->json([
                'status' => 400,
                'message' => 'You already have a pending offer',
                'errors' => ['offer' => ['Please wait for response to your pending offer']]
            ], 400);
        }

        DB::beginTransaction();

        try {
            $offer = $conversation->offers()->create([
                'sender_id' => $userId,
                'product_id' => $conversation->product_id,
                'offered_price' => $request->offered_price,
                'quantity' => $request->quantity ?? 1,
                'notes' => $request->notes,
                'status' => 'pending',
                'expires_at' => $request->expires_at ?? now()->addDays(3),
            ]);

            // Create system message about the offer
            $message = $conversation->messages()->create([
                'sender_id' => $userId,
                'message' => 'Sent a price offer',
                'type' => 'offer',
                'metadata' => [
                    'offer_id' => $offer->id,
                    'offered_price' => $offer->offered_price,
                    'quantity' => $offer->quantity,
                ],
                'is_read' => false,
            ]);

            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            return response()->json([
                'status' => 201,
                'message' => 'Offer sent successfully',
                'data' => new OfferResource($offer),
                'errors' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create offer: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create offer',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }

    public function update(Request $request, Offer $offer)
    {
        $userId = $request->user()->id;

        // Check if user is the recipient (seller/buyer depending on who sent the offer)
        $conversation = $offer->conversation;
        $isRecipient = ($offer->sender_id === $conversation->buyer_id) 
            ? $userId === $conversation->seller_id 
            : $userId === $conversation->buyer_id;

        if (!$isRecipient) {
            return response()->json([
                'status' => 403,
                'message' => 'Only the recipient can respond to this offer',
                'errors' => ['permission' => ['Unauthorized']]
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected,countered',
            'counter_price' => 'required_if:status,countered|numeric|min:0.01',
            'counter_notes' => 'required_if:status,countered|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = $offer->status;
            $offer->status = $request->status;
            
            if ($request->status === 'countered') {
                // Create new offer with countered price
                $newOffer = $conversation->offers()->create([
                    'sender_id' => $userId,
                    'product_id' => $conversation->product_id,
                    'offered_price' => $request->counter_price,
                    'quantity' => $offer->quantity,
                    'notes' => $request->counter_notes,
                    'status' => 'pending',
                    'expires_at' => now()->addDays(3),
                ]);

                // Create message about counter offer
                $conversation->messages()->create([
                    'sender_id' => $userId,
                    'message' => 'Sent a counter offer',
                    'type' => 'offer',
                    'metadata' => [
                        'offer_id' => $newOffer->id,
                        'offered_price' => $newOffer->offered_price,
                        'original_offer_id' => $offer->id,
                    ],
                    'is_read' => false,
                ]);
            } else {
                // Create message about acceptance/rejection
                $conversation->messages()->create([
                    'sender_id' => $userId,
                    'message' => $request->status === 'accepted' 
                        ? 'Offer accepted!' 
                        : 'Offer rejected',
                    'type' => 'text',
                    'metadata' => ['offer_id' => $offer->id],
                    'is_read' => false,
                ]);
            }

            $offer->save();
            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Offer ' . $request->status . ' successfully',
                'data' => new OfferResource($offer),
                'errors' => []
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update offer: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update offer',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }
}