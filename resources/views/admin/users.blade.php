@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
    <!-- Search and Filter Bar -->
    <div class="card">
        <form method="GET" action="{{ route('admin.users') }}" class="search-bar">
            <input type="text" name="search" placeholder="Search by name, email, phone, or ID..." 
                   value="{{ request('search') }}">
            
            <select name="filter" class="filter-select" onchange="this.form.submit()">
                <option value="all">All Users</option>
                <option value="admin" {{ request('filter') == 'admin' ? 'selected' : '' }}>Admins Only</option>
                <option value="regular" {{ request('filter') == 'regular' ? 'selected' : '' }}>Regular Users</option>
                <option value="verified" {{ request('filter') == 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="unverified" {{ request('filter') == 'unverified' ? 'selected' : '' }}>Unverified</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('admin.users') }}" class="btn btn-warning">Clear</a>
        </form>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ $users->total() }}</div>
            <div class="stat-label">Total Users</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">{{ \App\Models\User::where('is_admin', true)->count() }}</div>
            <div class="stat-label">Admin Users</div>
        </div>
        
        <div class="stat-card accepted">
            <div class="stat-value">{{ \App\Models\User::whereNotNull('email_verified_at')->count() }}</div>
            <div class="stat-label">Verified Users</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">{{ \App\Models\Load::distinct('user_id')->count('user_id') }}</div>
            <div class="stat-label">Users with Orders</div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            Users ({{ $users->total() }} total)
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <small style="color: #999;">ID: {{ substr($user->id, -8) }}</small>
                            </td>
                            <td>
                                {{ $user->email }}
                                @if($user->email_verified_at)
                                    <span style="color: #27ae60; font-size: 12px;">✓</span>
                                @else
                                    <span style="color: #e74c3c; font-size: 12px;">✗</span>
                                @endif
                            </td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                <strong>{{ $user->loads_count }}</strong> orders
                            </td>
                            <td>
                                @if($user->is_admin)
                                    <span class="status-badge" style="background: #9b59b6; color: white;">Admin</span>
                                @else
                                    <span class="status-badge status-pending">User</span>
                                @endif
                                
                                @if($user->quickbooks_id)
                                    <br><span class="status-badge status-accepted" style="margin-top: 5px; display: inline-block;">QB Connected</span>
                                @endif
                            </td>
                            <td>
                                {{ $user->created_at->format('M d, Y') }}<br>
                                <small>{{ $user->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="action-buttons">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                
                                @if($user->id !== Auth::id())
                                    <form action="{{ route('admin.users.toggle-admin', $user->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        @if($user->is_admin)
                                            <button type="submit" class="btn btn-warning btn-sm" 
                                                    onclick="return confirm('Remove admin privileges from {{ $user->name }}?')">
                                                Remove Admin
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Make {{ $user->name }} an admin?')">
                                                Make Admin
                                            </button>
                                        @endif
                                    </form>
                                    
                                    @if($user->loads_count == 0)
                                        <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" 
                                              style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    @endif
                                @else
                                    <span style="color: #999; font-size: 12px;">Current User</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                No users found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="pagination">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

@section('styles')
<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>
@endsection