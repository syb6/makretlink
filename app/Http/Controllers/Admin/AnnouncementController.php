<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements', [
            'announcements' => Announcement::with('author')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        Announcement::create([
            'created_by' => auth()->id(),
            'title' => $data['title'],
            'message' => $data['message'],
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return back()->with('success', 'Announcement created.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        if ($data['status'] === 'published' && ! $announcement->published_at) {
            $data['published_at'] = now();
        }

        $announcement->update($data);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }
}
