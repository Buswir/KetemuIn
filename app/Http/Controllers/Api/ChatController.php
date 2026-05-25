<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Item;
use App\Models\Message;
use Illuminate\Http\Request;
use Exception;

class ChatController extends Controller
{
    /**
     * Get all active conversations for the authenticated user.
     */
    public function getConversations()
    {
        try {
            $user = auth()->user();
            $conversations = Conversation::where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->with(['item', 'sender', 'receiver'])
                ->latest('updated_at')
                ->get();

            return ConversationResource::collection($conversations);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve conversations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation.
     */
    public function getMessages($conversationId)
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            // Authorization
            if ($conversation->sender_id !== auth()->id() && $conversation->receiver_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

            // Mark other user's messages as read
            $conversation->messages()
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return MessageResource::collection($messages);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve messages.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'item_id' => 'required_without:conversation_id|exists:items,id',
            'conversation_id' => 'exists:conversations,id',
            'message_text' => 'required|string|max:1000',
        ]);

        try {
            $conversationId = $request->conversation_id;

            if (!$conversationId) {
                // Find or create conversation based on item_id
                $item = Item::findOrFail($request->item_id);
                
                if ($item->user_id === auth()->id()) {
                    return response()->json(['message' => 'You cannot start a chat with yourself.'], 400);
                }

                $conversation = Conversation::firstOrCreate([
                    'item_id' => $item->id,
                    'sender_id' => auth()->id(),
                    'receiver_id' => $item->user_id,
                ]);
                $conversationId = $conversation->id;
            } else {
                $conversation = Conversation::findOrFail($conversationId);
                
                // Authorization
                if ($conversation->sender_id !== auth()->id() && $conversation->receiver_id !== auth()->id()) {
                    return response()->json(['message' => 'Unauthorized.'], 403);
                }
            }

            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => auth()->id(),
                'message_text' => $request->message_text,
            ]);

            // Touch conversation to update timestamps
            $conversation->touch();

            return new MessageResource($message);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to send message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
