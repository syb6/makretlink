<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

    /** Simple contact form handler (no mail transport required for demo). */
    public function send(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // In a production build this would dispatch an email/notification.
        return back()->with('success', 'Thanks for reaching out! We will get back to you soon.');
    }
}
