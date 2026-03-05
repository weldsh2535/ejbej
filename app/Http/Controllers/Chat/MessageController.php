<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
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

        // Check if conversation is active
        if ($conversation->status !== 'active') {
            return response()->json([
                'status' => 400,
                'message' => 'Conversation is not active',
                'errors' => ['conversation' => ['This conversation is ' . $conversation->status]]
            ], 400);
        }

        $request->validate([
            'message' => 'required_without:image|string|max:5000',
            'type' => 'nullable|in:text,image',
            'image' => 'required_if:type,image|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        DB::beginTransaction();

        try {
            $messageData = [
                'sender_id' => $userId,
                'type' => $request->type ?? 'text',
                'is_read' => false,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('chat-images', 'public');
                $messageData['message'] = 'Sent an image';
                $messageData['metadata'] = [
                    'image_url' => asset('storage/' . $path),
                    'image_path' => $path
                ];
            } else {
                $messageData['message'] = $request->message;
            }

            $message = $conversation->messages()->create($messageData);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            // Broadcast event for real-time (if using websockets)
            // broadcast(new NewMessage($message))->toOthers();

            return response()->json([
                'status' => 201,
                'message' => 'Message sent successfully',
                'data' => new MessageResource($message->load('sender')),
                'errors' => []
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to send message: ' . $e->getMessage());
            
            return response()->json([
                'status' => 500,
                'message' => 'Failed to send message',
                'errors' => ['server' => ['Internal server error']]
            ], 500);
        }
    }

    public function markAsRead(Request $request, Conversation $conversation)
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

        $conversation->markAsRead($userId);

        return response()->json([
            'status' => 200,
            'message' => 'Messages marked as read',
            'errors' => []
        ], 200);
    }

    public function destroy(Request $request, Message $message)
    {
        $userId = $request->user()->id;

        // Check if user is the sender
        if ($message->sender_id !== $userId) {
            return response()->json([
                'status' => 403,
                'message' => 'You can only delete your own messages',
                'errors' => ['permission' => ['Unauthorized']]
            ], 403);
        }

        // Check if message is not too old (e.g., within 24 hours)
        if ($message->created_at->diffInHours(now()) > 24) {
            return response()->json([
                'status' => 400,
                'message' => 'Cannot delete messages older than 24 hours',
                'errors' => ['message' => ['Message too old to delete']]
            ], 400);
        }

        // If it's an image message, delete the image file
        if ($message->type === 'image' && isset($message->metadata['image_path'])) {
            Storage::disk('public')->delete($message->metadata['image_path']);
        }

        $message->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Message deleted successfully',
            'errors' => []
        ], 200);
    }
}