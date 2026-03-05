<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|in:active,archived,blocked'
        ]);

        $perPage = $request->get('per_page', 15);
        $userId = $request->user()->id;

        $conversations = Conversation::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with(['product', 'latestMessage', 'buyer', 'seller'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'message' => 'Conversations retrieved successfully',
            'data' => ConversationResource::collection($conversations),
            'pagination' => [
                'total' => $conversations->total(),
                'per_page' => $conversations->perPage(),
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ],
            'errors' => []
        ], 200);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        // Check if user is part of conversation
        if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized to view this conversation',
                'errors' => ['permission' => ['You do not have access to this conversation']]
            ], 403);
        }

        // Mark messages as read
        $conversation->markAsRead($userId);

        // Load messages with pagination
        $messages = $conversation->messages()
            ->with('sender')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'status' => 200,
            'message' => 'Conversation retrieved successfully',
            'data' => [
                'conversation' => new ConversationResource($conversation->load(['product', 'buyer', 'seller'])),
                'messages' => MessageResource::collection($messages),
                'pagination' => [
                    'total' => $messages->total(),
                    'per_page' => $messages->perPage(),
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'next_page_url' => $messages->nextPageUrl(),
                    'prev_page_url' => $messages->previousPageUrl(),
                ],
            ],
            'errors' => []
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'initial_message' => 'required|string|max:1000'
        ]);

        $product = Product::findOrFail($request->product_id);
        $buyerId = $request->user()->id;
        $sellerId = $product->user_id;

        DB::beginTransaction();

        try {
            // Find or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'buyer_id' => $buyerId,
                    'seller_id' => $sellerId,
                ],
                [
                    'title' => $product->title,
                    'status' => 'active',
                    'last_message_at' => now(),
                ]
            );

            // Create initial message
            $message = $conversation->messages()->create([
                'sender_id' => $buyerId,
                'message' => $request->initial_message,
                'type' => 'text',
                'is_read' => false,
            ]);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            // Return simple success response without resources
            return response()->json([
                'status' => 201,
                'message' => 'Conversation started successfully',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'product_id' => $product->id
                ],
                'errors' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to start conversation: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to start conversation',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }

    public function update(Request $request, Conversation $conversation)
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
            'status' => 'required|in:active,archived,blocked'
        ]);

        $conversation->update($request->only('status'));

        return response()->json([
            'status' => 200,
            'message' => 'Conversation updated successfully',
            'data' => new ConversationResource($conversation),
            'errors' => []
        ], 200);
    }

    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $count = Conversation::where(function ($query) use ($userId) {
            $query->where('buyer_id', $userId)
                ->orWhere('seller_id', $userId);
        })
            ->where('status', 'active')
            ->withCount(['messages' => function ($query) use ($userId) {
                $query->where('sender_id', '!=', $userId)
                    ->where('is_read', false);
            }])
            ->get()
            ->sum('messages_count');

        return response()->json([
            'status' => 200,
            'data' => ['unread_count' => $count],
            'errors' => []
        ], 200);
    }
}
