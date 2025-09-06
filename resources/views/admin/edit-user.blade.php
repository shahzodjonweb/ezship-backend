@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User: ' . $user->name)

@section('content')
    <div class="card">
        <div class="card-header">User Information</div>
        
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label>User ID</label>
                <input type="text" value="{{ $user->id }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                @error('phone')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label>QuickBooks ID</label>
                <input type="text" value="{{ $user->quickbooks_id ?? 'Not Connected' }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Email Verification</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if($user->email_verified_at)
                        <span class="status-badge status-accepted">Verified on {{ $user->email_verified_at->format('M d, Y h:i A') }}</span>
                    @else
                        <span class="status-badge status-rejected">Not Verified</span>
                        <label style="margin: 0;">
                            <input type="checkbox" name="verify_email" value="1" style="width: auto; margin-right: 5px;">
                            Verify email now
                        </label>
                    @endif
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }}
                           style="width: auto; margin-right: 10px;"
                           {{ $user->id === Auth::id() ? 'disabled' : '' }}>
                    Administrator Privileges
                </label>
                @if($user->id === Auth::id())
                    <small style="color: #999;">You cannot modify your own admin status</small>
                @endif
            </div>
            
            <div class="form-group">
                <label>Account Created</label>
                <input type="text" value="{{ $user->created_at->format('M d, Y h:i A') }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Last Updated</label>
                <input type="text" value="{{ $user->updated_at->format('M d, Y h:i A') }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Total Orders</label>
                <input type="text" value="{{ $user->loads_count }} orders" disabled>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="{{ route('admin.users') }}" class="btn btn-warning">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Recent Orders -->
    @if($recentOrders && count($recentOrders) > 0)
    <div class="card">
        <div class="card-header">Recent Orders</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td>{{ substr($order->id, -8) }}</td>
                            <td>{{ $order->type ?? 'N/A' }}</td>
                            <td>{{ Str::limit($order->description, 50) }}</td>
                            <td>
                                <span class="status-badge status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>${{ number_format($order->initial_price, 2) }}</td>
                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.edit', $order->id) }}" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Password Reset -->
    <div class="card">
        <div class="card-header">Reset Password</div>
        <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
            @csrf
            @method('PATCH')
            
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" required minlength="8">
                @error('password')
                    <small style="color: #e74c3c;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required minlength="8">
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    </div>

    <!-- Delete User -->
    @if($user->id !== Auth::id())
        <div class="card" style="border-left: 4px solid #e74c3c;">
            <div class="card-header">Danger Zone</div>
            
            @if($user->loads_count > 0)
                <p style="color: #e74c3c;">
                    <strong>Cannot delete user:</strong> This user has {{ $user->loads_count }} order(s). 
                    Users with orders cannot be deleted to maintain data integrity.
                </p>
                <p>Consider deactivating the user or removing their admin privileges instead.</p>
            @else
                <p>Once you delete a user, there is no going back. Please be certain.</p>
                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" 
                      onsubmit="return confirm('Are you absolutely sure you want to delete {{ $user->name }}? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete This User</button>
                </form>
            @endif
        </div>
    @endif
@endsection