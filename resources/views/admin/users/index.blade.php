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
                    <input type="hidden" name="role" value="{{ $role }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="approval" value="{{ $approval }}">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or email..." value="{{ $search }}">

                    {{-- Bootstrap dropdowns instead of native <select> popups:
                         the OS-rendered select list cannot be themed, these can. --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-ml dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ ['admin' => 'Admins', 'farmer' => 'Farmers', 'customer' => 'Customers'][$role] ?? 'All roles' }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><button type="button" class="dropdown-item filter-option" data-name="role" data-value="">All roles</button></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="role" data-value="admin">Admins</button></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="role" data-value="farmer">Farmers</button></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="role" data-value="customer">Customers</button></li>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-ml dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ ['pending' => 'Pending approval', 'approved' => 'Approved', 'rejected' => 'Rejected'][$approval] ?? ($status ? ucfirst($status) : 'All statuses') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Account status</h6></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="status" data-value="">All statuses</button></li>
                            @foreach (['active', 'inactive', 'suspended'] as $s)
                                <li><button type="button" class="dropdown-item filter-option" data-name="status" data-value="{{ $s }}">{{ ucfirst($s) }}</button></li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Farmer application</h6></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="approval" data-value="pending">Pending approval</button></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="approval" data-value="approved">Approved</button></li>
                            <li><button type="button" class="dropdown-item filter-option" data-name="approval" data-value="rejected">Rejected</button></li>
                        </ul>
                    </div>

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
                                                        <!-- themed via --bs-btn-* overrides (olive, not bootstrap green) -->
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
                                                    <!-- keep .btn-success: CSS override layer re-skins it to olive -->
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

@push('scripts')
<script>
    // Filter dropdowns: copy the picked option into the matching hidden input.
    document.querySelectorAll('.filter-option').forEach(function (option) {
        option.addEventListener('click', function () {
            var input = document.querySelector('form input[name="' + option.dataset.name + '"]');
            if (input) input.value = option.dataset.value;
        });
    });
</script>
@endpush