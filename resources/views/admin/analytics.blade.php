@extends('layouts.admin')

@section('title', 'Analytics')
@section('page-title', 'Analytics & Reports')

@section('content')
    <!-- Date Range Selector -->
    <div class="card">
        <form method="GET" action="{{ route('admin.analytics') }}" class="search-bar">
            <label style="margin-bottom: 0; margin-right: 10px; align-self: center;">Date Range:</label>
            <input type="date" name="start_date" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
            <input type="date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}">
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="{{ route('admin.analytics') }}" class="btn btn-warning">Reset</a>
        </form>
    </div>

    <!-- Revenue Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">${{ number_format($revenue_stats['total_revenue'], 2) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">${{ number_format($revenue_stats['average_order_value'], 2) }}</div>
            <div class="stat-label">Average Order Value</div>
        </div>
        
        <div class="stat-card accepted">
            <div class="stat-value">${{ number_format($revenue_stats['accepted_revenue'], 2) }}</div>
            <div class="stat-label">Accepted Orders Revenue</div>
        </div>
        
        <div class="stat-card invoiced">
            <div class="stat-value">${{ number_format($revenue_stats['invoiced_revenue'], 2) }}</div>
            <div class="stat-label">Invoiced Revenue</div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="card">
        <div class="card-header">Order Status Distribution</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($status_distribution as $status)
                        <tr>
                            <td>
                                <span class="status-badge status-{{ $status->status }}">
                                    {{ ucfirst($status->status) }}
                                </span>
                            </td>
                            <td>{{ $status->count }}</td>
                            <td>{{ $status->percentage }}%</td>
                            <td>${{ number_format($status->total_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card">
        <div class="card-header">Top Customers</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Average Order</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($top_customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->total_orders }}</td>
                            <td>${{ number_format($customer->total_spent, 2) }}</td>
                            <td>${{ number_format($customer->average_order, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                No customer data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="card">
        <div class="card-header">Monthly Order Trends</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Orders</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Revenue</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthly_trends as $trend)
                        <tr>
                            <td>{{ $trend->month_name }}</td>
                            <td>{{ $trend->total_orders }}</td>
                            <td>{{ $trend->completed_orders }}</td>
                            <td>{{ $trend->cancelled_orders }}</td>
                            <td>${{ number_format($trend->revenue, 2) }}</td>
                            <td>
                                @if($trend->growth > 0)
                                    <span style="color: #27ae60;">+{{ $trend->growth }}%</span>
                                @elseif($trend->growth < 0)
                                    <span style="color: #e74c3c;">{{ $trend->growth }}%</span>
                                @else
                                    <span>0%</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                No monthly data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Types Distribution -->
    <div class="card">
        <div class="card-header">Order Types</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Average Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order_types as $type)
                        <tr>
                            <td>{{ $type->type ?? 'Unspecified' }}</td>
                            <td>{{ $type->count }}</td>
                            <td>{{ $type->percentage }}%</td>
                            <td>${{ number_format($type->average_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                No order type data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card">
        <div class="card-header">Export Reports</div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <form action="{{ route('admin.analytics') }}" method="GET" style="display: inline;">
                <input type="hidden" name="export" value="csv">
                <input type="hidden" name="start_date" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                <input type="hidden" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                <button type="submit" class="btn btn-primary">Export to CSV</button>
            </form>
            
            <form action="{{ route('admin.analytics') }}" method="GET" style="display: inline;">
                <input type="hidden" name="export" value="pdf">
                <input type="hidden" name="start_date" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                <input type="hidden" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                <button type="submit" class="btn btn-primary">Export to PDF</button>
            </form>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .search-bar {
        align-items: center;
    }
    
    .search-bar input[type="date"] {
        width: auto;
        max-width: 200px;
    }
</style>
@endsection