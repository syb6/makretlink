@extends('layouts.app')

@section('title', 'My Products')

@section('content')
<section class="py-4 py-md-5">
    <div class="container">
        <span class="badge-sub">FARMER MANAGEMENT PORTAL</span>
        <h1 class="section-title mb-4">My Products</h1>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="dashboard-card-header"><h3 class="h6 mb-0"><i class="bi bi-plus-circle text-success me-2"></i>Add Product</h3></div>
                    <div class="dashboard-card-body">
                        <x-form-errors />
<form method="POST" action="{{ route('farmer.products.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Name</label>
                                <input type="text" name="name" class="form-control form-control-sm" required value="{{ old('name') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Category</label>
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="">Uncategorized</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Price (Rs)</label>
                                    <input type="number" step="0.01" min="0.01" name="price" class="form-control form-control-sm" required value="{{ old('price') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Unit</label>
                                    <input type="text" name="unit" class="form-control form-control-sm" placeholder="kg, bunch, pc" required value="{{ old('unit') }}">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Description</label>
                                <textarea name="description" rows="2" class="form-control form-control-sm"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="active" selected>Active — visible to customers</option>
                                    <option value="inactive">Inactive — hidden</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Product picture</label>
                                @include('components.image-picker', ['name' => 'image', 'label' => 'product picture', 'placeholder' => 'bi-basket2'])
                            </div>
                            <button class="btn btn-ml w-100 btn-sm">Add product</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="dashboard-card flush">
                    <div class="table-responsive">
                        <table class="table table-ml align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr {{ $product->trashed() ? 'class="opacity-50"' : '' }}>
                                        <td>
                                            <strong>{{ $product->name }}</strong>
                                            @if ($product->trashed())
                                                <span class="badge bg-secondary">deleted</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $product->category?->name ?? '—' }}</td>
                                        <td class="small">Rs {{ number_format($product->price, 2) }} / {{ $product->unit }}</td>
                                        <td><span class="status-pill status-{{ $product->status }}">{{ $product->status }}</span></td>
                                        <td class="text-end text-nowrap">
                                            @unless ($product->trashed())
                                                <button class="btn btn-sm btn-outline-ml" data-bs-toggle="modal" data-bs-target="#editProduct-{{ $product->id }}"><i class="bi bi-pencil"></i></button>
                                                <form method="POST" action="{{ route('farmer.products.destroy', $product) }}" class="d-inline" data-confirm="Delete this product?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('farmer.products.restore', $product->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No products yet — add your first one!</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">{{ $products->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Edit modals --}}
@foreach ($products as $product)
    @unless ($product->trashed())
        <div class="modal fade" id="editProduct-{{ $product->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('farmer.products.update', $product) }}" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit {{ $product->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $product->name }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">Uncategorized</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" {{ $product->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Price (Rs)</label>
                                <input type="number" step="0.01" min="0.01" name="price" class="form-control form-control-sm" value="{{ $product->price }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Unit</label>
                                <input type="text" name="unit" class="form-control form-control-sm" value="{{ $product->unit }}" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm">{{ $product->description }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Product picture</label>
                            @include('components.image-picker', ['name' => 'image', 'id' => 'image-'.$product->id, 'label' => 'product picture', 'existing' => $product->image, 'placeholder' => 'bi-basket2'])
                        </div>
                        <div>
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active" {{ $product->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $product->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-ml">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
@endforeach
@endsection
