<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
        );
    }

    public function read(Request $request, DatabaseNotification $notification)
    {
        //TODO: Validar que a notificação é do utilizador
        $notification->markAsRead();
        return response()->json($notification->fresh());
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
    }
}
