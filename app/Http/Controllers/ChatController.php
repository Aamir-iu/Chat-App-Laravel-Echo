<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use App\Http\Resources\ChatResource;
use App\Events\PrivateChatEvent;
use Carbon\Carbon;
use App\Events\MsgReadEvent;

class ChatController extends Controller
{
    /**
     * Send message.
     *
     * @return void
     */
    public function send(Session $session, Request $request)
    {
        $message = $session->messages()->create(['content' => $request->content]);

        $chat = $message->createForSend($session->id);

        $message->createForReceive($session->id, $request->to_user);

        broadcast(new PrivateChatEvent($message->content, $chat));

        return response($chat->id, 200);
    }
    /**
     * Load all chats.
     *
     * @return void
     */

    public function chats(Session $session)
    {
        return ChatResource::collection($session->chats->where('user_id', auth()->id()));
    }

   
    /**
     * Delete all chat.
     *
     * @return void
     */

    public function clear(Session $session)
    {
        $session->deleteChats();
        $session->chats->count() == 0 ? $session->deleteMessages() : '';
        return response('cleared', 200);
    }
}
