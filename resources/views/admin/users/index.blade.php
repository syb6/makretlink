@extends('layouts.app')

@section('title', 'Users & Farmers')
@section('admin_title', 'Users & Farmers')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="h3 mb-1">Users &amp; Farmers</h2>
                    <p class="text-muted mb-0">Approve farmer applications and manage account status.</p>
                </div>
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ $search }}">
                    <select name="role" class="form-select form-select-sm w-auto">
                        <option value="">All roles</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admins</option>
                        <option value="farmer" {{ $role === 'farmer' ? 'selected' : '' }}>Farmers</option>
                        <option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>Customers</option>
                    </select>
                    <select name="status" class="form-select form-select-sm w-auto">
                        <option value="">All statuses</option>
                        @foreach (['active', 'inactive', 'suspended'] as $s)
                            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-ml btn-sm">Filter</button>
                </form>
            </div>

            <div class="dashboard-card flush">
                <div class="table-responsive">
                    <table class="table table-ml align-middle mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Extra</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        <div class="small text-muted">{{ $user->email }} · {{ $user->phone }}</div>
                                    </td>
                                    <td><span class="badge badge-soft">{{ $user->role }}</span></td>
                                    <td><span class="status-pill status-{{ $user->status }}">{{ ucfirst($user->status) }}</span></td>
                                    <td class="small">
                                        @if ($user->farmerProfile)
                                            {{ $user->farmerProfile->business_name }}
                                            <span class="status-pill status-{{ $user->farmerProfile->approval_status }}">{{ ucfirst($user->farmerProfile->approval_status) }}</span>
                                        @elseif ($user->customerProfile)
                                            <span class="text-muted">customer</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $user->isAdmin())
                                            {{-- Status Action Buttons --}}
                                            @if ($user->status !== 'active')
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="active">
                                                    <button class="btn btn-sm btn-outline-success" title="Set Active">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($user->status !== 'inactive')
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="inactive">
                                                    <button class="btn btn-sm btn-outline-secondary" title="Set Inactive">
                                                        <i class="bi bi-pause-circle"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($user->status !== 'suspended')
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="suspended">
                                                    <button class="btn btn-sm btn-outline-warning" title="Set Suspended">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Farmer Profile Approval Buttons --}}
                                            @if ($user->farmerProfile && $user->farmerProfile->approval_status === 'pending')
                                                <form method="POST" action="{{ route('admin.farmers.approve', $user) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success ms-1" title="Approve Farmer">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.farmers.reject', $user) }}" class="d-inline" data-confirm="Reject this farmer?">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger" title="Reject Farmer">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted small">admin</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $users->links() }}</div>
            </div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink Administration Portal.</p>
            </div>
        </footer>
    </div>
</div>
@endsection