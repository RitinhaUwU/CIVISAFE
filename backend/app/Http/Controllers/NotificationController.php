<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();
        return NotificationResource::collection($notifications);
    }

    public function read(Request $request, DatabaseNotification $notification)
    {
        if($notification->notifiable_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();
        return new NotificationResource($notification->fresh());
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
    }
}
