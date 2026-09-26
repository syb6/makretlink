@extends('layouts.app')

@section('title', 'Product Categories')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <div class="col-lg-2 col-md-3">@include('layouts.partials.admin-sidebar')</div>

        <div class="col-lg-10 col-md-9">
            <h1 class="section-title mb-4">Product <span class="accent">Categories</span></h1>

            <div class="card-ml p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Add category</h5>
                <form method="POST" action="{{ route('admin.categories.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-ml btn-sm w-100">Add</button>
                    </div>
                </form>
            </div>

            <div class="card-ml p-2">
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
                <div class="p-2">{{ $categories->links() }}</div>
            </div>
        </div>
    </div>
</div>

@foreach ($categories as $category)
    <div class="modal fade" id="editCategory-{{ $category->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="modal-content">
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
