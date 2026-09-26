@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Users & <span class="accent">Farmers</span></h1>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All roles</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admins</option>
                        <option value="farmer" {{ $role === 'farmer' ? 'selected' : '' }}>Farmers</option>
                        <option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>Customers</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach (['active', 'inactive', 'suspended'] as $s)
                            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-ml btn-sm w-100">Filter</button></div>
            </form>

            <div class="card-ml p-2">
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
                                    <td><span class="status-pill status-{{ $user->status }}">{{ $user->status }}</span></td>
                                    <td class="small">
                                        @if ($user->farmerProfile)
                                            {{ $user->farmerProfile->business_name }}
                                            <span class="status-pill status-{{ $user->farmerProfile->approval_status }}">{{ $user->farmerProfile->approval_status }}</span>
                                        @elseif ($user->customerProfile)
                                            <span class="text-muted">customer</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $user->isAdmin())
                                            @foreach (['active', 'inactive', 'suspended'] as $s)
                                                @if ($user->status !== $s)
                                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $s }}">
                                                        <button class="btn btn-sm btn-outline-secondary" title="Set {{ $s }}">{{ ucfirst(substr($s, 0, 1)) }}</button>
                                                    </form>
                                                @endif
                                            @endforeach

                                            @if ($user->farmerProfile && $user->farmerProfile->approval_status === 'pending')
                                                <form method="POST" action="{{ route('admin.farmers.approve', $user) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.farmers.reject', $user) }}" class="d-inline" data-confirm="Reject this farmer?">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger">Reject</button>
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
                <div class="p-2">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
