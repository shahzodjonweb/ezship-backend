@extends('layouts.admin')

@section('title', 'Edit Order')
@section('page-title', 'Edit Order #' . substr($order->id, -8))

@section('content')
    <div class="card">
        <div class="card-header">Order Details</div>
        
        <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label>Order ID</label>
                <input type="text" value="{{ $order->id }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Customer</label>
                @if($order->user)
                    <input type="text" value="{{ $order->user->name }} ({{ $order->user->email }})" disabled>
                @else
                    <input type="text" value="No user assigned" disabled>
                @endif
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $order->status == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label>Type</label>
                <input type="text" value="{{ $order->type }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4">{{ $order->description }}</textarea>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="{{ $order->phone }}">
            </div>
            
            <div class="form-group">
                <label>Initial Price</label>
                <input type="number" name="initial_price" value="{{ $order->initial_price }}" step="0.01" min="0">
            </div>
            
            <div class="form-group">
                <label>Counter Price</label>
                <input type="number" name="counter_price" value="{{ $order->counter_price }}" step="0.01" min="0">
            </div>
            
            <div class="form-group">
                <label>Created At</label>
                <input type="text" value="{{ $order->created_at->format('M d, Y h:i A') }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Updated At</label>
                <input type="text" value="{{ $order->updated_at->format('M d, Y h:i A') }}" disabled>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="{{ route('admin.orders') }}" class="btn btn-warning">Cancel</a>
            </div>
        </form>
    </div>

    @if($order->categories && count($order->categories) > 0)
    <div class="card">
        <div class="card-header">Categories</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($order->stops && count($order->stops) > 0)
    <div class="card">
        <div class="card-header">Stops & Locations</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Stop #</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th>ZIP</th>
                        <th>Date</th>
                        <th>Coordinates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->stops as $index => $stop)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $stop->location->address ?? 'N/A' }}</td>
                            <td>{{ $stop->location->city ?? 'N/A' }}</td>
                            <td>{{ $stop->location->state ?? 'N/A' }}</td>
                            <td>{{ $stop->location->zip ?? 'N/A' }}</td>
                            <td>{{ $stop->date ?? 'N/A' }}</td>
                            <td>
                                @if($stop->location && $stop->location->lat && $stop->location->lon)
                                    <small>{{ $stop->location->lat }}, {{ $stop->location->lon }}</small>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($order->payment)
    <div class="card">
        <div class="card-header">Payment Information</div>
        <div class="form-group">
            <label>Invoice ID</label>
            <input type="text" value="{{ $order->payment->invoice_id ?? 'N/A' }}" disabled>
        </div>
        <div class="form-group">
            <label>Payment Created</label>
            <input type="text" value="{{ $order->payment->created_at ? $order->payment->created_at->format('M d, Y h:i A') : 'N/A' }}" disabled>
        </div>
    </div>
    @endif

    <!-- Quick Status Change -->
    <div class="card">
        <div class="card-header">Quick Status Change</div>
        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
            @csrf
            @method('PATCH')
            
            <div class="form-group">
                <label>Change Status To:</label>
                <select name="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ $order->status == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>

    <!-- Delete Order -->
    <div class="card" style="border-left: 4px solid #e74c3c;">
        <div class="card-header">Danger Zone</div>
        <p>Once you delete an order, there is no going back. Please be certain.</p>
        <form action="{{ route('admin.orders.delete', $order->id) }}" method="POST" 
              onsubmit="return confirm('Are you absolutely sure you want to delete this order? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete This Order</button>
        </form>
    </div>
@endsection