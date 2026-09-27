<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
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
     * Contact form: validates, then delivers the message to the site inbox
     * via Resend. Fails soft — a mail outage shows a friendly flash and is
     * reported, never a stack trace. Without RESEND_API_KEY the framework's
     * log transport silently records the email instead of sending.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            Mail::to(config('mail.from.address'))
                ->send(new ContactMessage($data['name'], $data['email'], $data['message']));

            return back()->with('success', 'Thanks for reaching out! Your message is on its way — we will get back to you soon.');
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Your message could not be sent right now — the mail service seems unreachable. Please email us directly at '.config('mail.from.address').'.');
        }
    }
}
