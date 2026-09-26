<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Shared inbox for every role (SRS: "in-app alerts for order updates").
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable_id === Auth::id(), 403);

        $notification->markAsRead();

        // Clicking a notification deep-links to the order; unread state is cleared first.
        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect($url)->with('success', 'Marked as read.');
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /** Small polling endpoint so the navbar badge stays fresh without page reloads. */
    public function count()
    {
        return response()->json([
            'unread' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
