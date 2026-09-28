<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\FeedbackMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Contact form: validates, stores the message in the admin Feedback
     * inbox, then delivers a copy to the site inbox via Resend. Mail fails
     * soft — an outage is reported and logged but the visitor still sees a
     * friendly confirmation, because the stored row is the source of truth.
     * Without RESEND_API_KEY the framework's log transport silently records
     * the email instead of sending.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        FeedbackMessage::create($data);

        try {
            Mail::to(config('mail.from.address'))
                ->send(new ContactMessage($data['name'], $data['email'], $data['message']));
        } catch (Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Thanks for reaching out! Your message is on its way — we will get back to you soon.');
    }
}
