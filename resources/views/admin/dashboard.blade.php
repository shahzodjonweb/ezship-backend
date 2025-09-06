@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_orders'] }}</div>
            <div class="stat-label">Total Orders</div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-value">{{ $stats['pending_orders'] }}</div>
            <div class="stat-label">Pending Orders</div>
        </div>
        
        <div class="stat-card accepted">
            <div class="stat-value">{{ $stats['accepted_orders'] }}</div>
            <div class="stat-label">Accepted Orders</div>
        </div>
        
        <div class="stat-card invoiced">
            <div class="stat-value">{{ $stats['invoiced_orders'] }}</div>
            <div class="stat-label">Invoiced Orders</div>
        </div>
        
        <div class="stat-card rejected">
            <div class="stat-value">{{ $stats['rejected_orders'] }}</div>
            <div class="stat-label">Rejected Orders</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">{{ $stats['total_users'] }}</div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">${{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">Recent Orders</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_orders as $order)
                        <tr>
                            <td>{{ substr($order->id, -8) }}</td>
                            <td>
                                @if($order->user)
                                    {{ $order->user->name }}<br>
                                    <small>{{ $order->user->email }}</small>
                                @else
                                    <em>No user</em>
                                @endif
                            </td>
                            <td>{{ $order->type ?? 'N/A' }}</td>
                            <td>
                                <span class="status-badge status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>${{ number_format($order->initial_price, 2) }}</td>
                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                No orders found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ route('admin.orders') }}" class="btn btn-primary">View All Orders</a>
        </div>
    </div>
@endsection