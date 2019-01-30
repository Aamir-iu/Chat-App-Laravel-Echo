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

    //read session iff message is read

     public function read(Session $session)
    {
        $chats = $session->chats->where('read_at', null)->where('type', 0)->where('user_id', '!=', auth()->id());
        foreach ($chats as $chat) {
            $chat->update(['read_at' => Carbon::now()]);
            broadcast(new MsgReadEvent(new ChatResource($chat), $chat->session_id));
        }
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
