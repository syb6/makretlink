@extends('layouts.app')

@section('title', 'Product Categories')
@section('admin_title', 'Product Categories')

@section('content')
<div class="app-container">
    @include('layouts.partials.admin-sidebar')
    <div class="main-wrapper">
        @include('layouts.partials.admin-header')

        <main class="p-3 p-md-4">
            <div class="mb-4">
                <h2 class="h3 mb-1">Product Categories</h2>
                <p class="text-muted mb-0">Organize produce into categories customers can filter by.</p>
            </div>

            <div class="dashboard-card mb-4">
                <div class="dashboard-card-header"><h3 class="h5 mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Add Category</h3></div>
                <div class="dashboard-card-body">
                    <x-form-errors />
<form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-lg-4 col-md-5">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label small fw-semibold">Picture</label>
                            @include('components.image-picker', ['name' => 'image', 'id' => 'category-new', 'label' => 'category picture', 'hint' => 'Square, max 2 MB', 'placeholder' => 'bi-tags'])
                        </div>
                        <div class="col-lg-2">
                            <button class="btn btn-ml btn-sm w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-card flush">
                <div class="table-responsive">
                    <table class="table table-ml align-middle mb-0">
                        <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="fw-semibold">{{ $category->name }}</td>
                                    <td class="small text-muted">{{ $category->slug }}</td>
                                    <td class="small">{{ $category->products_count }}</td>
                                    <td><span class="status-pill status-{{ $category->status }}">{{ $category->status }}</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-ml" data-bs-toggle="modal" data-bs-target="#editCategory-{{ $category->id }}"><i class="bi bi-pencil"></i></button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" data-confirm="Delete this category?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $categories->links() }}</div>
            </div>
        </main>

        <footer class="admin-footer">
            <div class="container text-center">
                <p class="text-muted small mb-0">&copy; {{ now()->year }} MarketLink Administration Portal.</p>
            </div>
        </footer>
    </div>
</div>

@foreach ($categories as $category)
    <div class="modal fade" id="editCategory-{{ $category->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit {{ $category->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $category->name }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Picture</label>
                        @include('components.image-picker', ['name' => 'image', 'id' => 'category-'.$category->id, 'label' => 'category picture', 'hint' => 'Square, max 2 MB', 'existing' => $category->image, 'type' => 'category', 'placeholder' => 'bi-tags'])
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="active" {{ $category->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $category->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-ml">Save</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
