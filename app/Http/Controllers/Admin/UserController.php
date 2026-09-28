<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $status = $request->query('status');
        $search = $request->query('q');
        // "approval" filters the farmer-profile application status (pending /
        // approved / rejected), distinct from the user account status.
        $approval = $request->query('approval');

        $users = User::query()
            ->with(['farmerProfile', 'customerProfile'])
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($approval, fn ($q) => $q->whereHas('farmerProfile', fn ($fp) => $fp->where('approval_status', $approval)))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'status' => $status,
            'approval' => $approval,
            'search' => $search,
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate(['status' => ['required', 'in:active,inactive,suspended']]);

        abort_if($user->isAdmin() && $user->id !== auth()->id(), 403, 'Cannot modify other admins.');

        $user->update($data);

        return back()->with('success', 'User status updated.');
    }

    public function approveFarmer(User $user)
    {
        $profile = FarmerProfile::where('user_id', $user->id)->firstOrFail();

        $profile->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', $user->name.' approved as a farmer.');
    }

    public function rejectFarmer(User $user)
    {
        $profile = FarmerProfile::where('user_id', $user->id)->firstOrFail();

        $profile->update([
            'approval_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Farmer registration rejected.');
    }
}
