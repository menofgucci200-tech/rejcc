<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user()->id;
        $notifications = MemberNotification::where('user_id', $me)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'type', 'title', 'body', 'link', 'read_at', 'created_at']);

        $unread = MemberNotification::where('user_id', $me)->whereNull('read_at')->count();

        return response()->json(['ok' => true, 'unread' => $unread, 'notifications' => $notifications]);
    }

    public function markRead(Request $request, int $id)
    {
        MemberNotification::where('id', $id)->where('user_id', $request->user()->id)
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        MemberNotification::where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
