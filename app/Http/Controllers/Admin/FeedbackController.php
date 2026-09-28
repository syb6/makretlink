<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackMessage;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $messages = FeedbackMessage::query()
            ->orderByRaw('read_at IS NULL DESC') // unread first
            ->latest()
            ->paginate(15);

        return view('admin.feedback', [
            'messages' => $messages,
            'unreadCount' => FeedbackMessage::whereNull('read_at')->count(),
        ]);
    }

    public function toggleRead(Request $request, FeedbackMessage $message)
    {
        $message->update(['read_at' => $message->isRead() ? null : now()]);

        if ($request->boolean('ajax')) {
            return back();
        }

        return back()->with('success', $message->isRead() ? 'Marked as read.' : 'Marked as unread.');
    }

    public function destroy(FeedbackMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Feedback message deleted.');
    }
}
