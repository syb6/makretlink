<?php

namespace App\Http\Controllers;

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
        return redirect($this->readableUrl($notification))->with('success', 'Marked as read.');
    }

    public function markAllRead()
    {
        // One UPDATE ... WHERE read_at IS NULL — never hydrate the collection.
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /** Small polling endpoint so the navbar badge stays fresh without page reloads. */
    public function count()
    {
        return response()->json([
            'unread' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * The stored deep link is written for the notification's audience
     * (farmer/* or customer/* routes). If the reader's role can't open that
     * area, land them back on the inbox instead of a role-middleware 403.
     */
    private function readableUrl(DatabaseNotification $notification): string
    {
        $url = $notification->data['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return route('notifications.index');
        }

        // Only follow app-generated links: in-app relative paths or http(s) URLs.
        if (! str_starts_with($url, '/') && preg_match('#^https?://#i', $url) !== 1) {
            return route('notifications.index');
        }

        $topLevel = strtolower(explode('/', trim((string) parse_url($url, PHP_URL_PATH), '/'))[0] ?? '');

        if (in_array($topLevel, ['customer', 'farmer', 'admin'], true)
            && $topLevel !== strtolower((string) Auth::user()->role)) {
            return route('notifications.index');
        }

        return $url;
    }
}
