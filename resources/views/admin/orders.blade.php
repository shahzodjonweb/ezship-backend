@extends('layouts.admin')

@section('title', 'Orders Management')
@section('page-title', 'Orders Management')

@section('content')
    <!-- Search and Filter Bar -->
    <div class="card">
        <form method="GET" action="{{ route('admin.orders') }}" class="search-bar">
            <input type="text" name="search" placeholder="Search by ID, description, customer..." 
                   value="{{ request('search') }}">
            
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('admin.orders') }}" class="btn btn-warning">Clear</a>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header">
            Orders ({{ $orders->total() }} total)
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Counter Price</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>
                                <strong>{{ substr($order->id, -8) }}</strong><br>
                                <small style="color: #999;">{{ $order->id }}</small>
                            </td>
                            <td>
                                @if($order->user)
                                    <strong>{{ $order->user->name }}</strong><br>
                                    <small>{{ $order->user->email }}</small>
                                @else
                                    <em>No user</em>
                                @endif
                            </td>
                            <td>{{ $order->type ?? 'N/A' }}</td>
                            <td>
                                {{ Str::limit($order->description, 50) }}
                                @if(strlen($order->description) > 50)
                                    <br><small><a href="{{ route('admin.orders.edit', $order->id) }}">View full</a></small>
                                @endif
                            </td>
                            <td>{{ $order->phone ?? 'N/A' }}</td>
                            <td>
                                <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="status-select" onchange="if(confirm('Change status to ' + this.value + '?')) this.form.submit();">
                                        @foreach(['initial', 'pending', 'accepted', 'rejected', 'invoiced', 'completed', 'cancelled'] as $status)
                                            <option value="{{ $status }}" {{ $order->status == $status ? 'selected' : '' }}>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>${{ number_format($order->initial_price, 2) }}</td>
                            <td>
                                @if($order->counter_price)
                                    ${{ number_format($order->counter_price, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('M d, Y') }}<br>
                                <small>{{ $order->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('admin.orders.delete', $order->id) }}" method="POST" 
                                      style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this order?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 20px;">
                                No orders found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($orders->hasPages())
            <div class="pagination">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    .status-select {
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
        font-size: 12px;
        cursor: pointer;
    }
    
    .status-select:focus {
        outline: none;
        border-color: #3498db;
    }
</style>
@endsection