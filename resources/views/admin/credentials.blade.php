@extends('layouts.admin')

@section('title', 'Credentials')
@section('page-title', 'Credentials Management')

@section('content')
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>All Credentials</span>
            <a href="{{ route('admin.credentials.create') }}" class="btn btn-primary">Add New Credential</a>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Refresh Token</th>
                        <th>Access Token</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credentials as $credential)
                        <tr>
                            <td>{{ $credential->id }}</td>
                            <td><strong>{{ $credential->name }}</strong></td>
                            <td>
                                <span style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $credential->refresh_token }}">
                                    {{ $credential->refresh_token ? substr($credential->refresh_token, 0, 30) . '...' : 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <span style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $credential->access_token }}">
                                    {{ $credential->access_token ? substr($credential->access_token, 0, 30) . '...' : 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $credential->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $credential->updated_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('admin.credentials.edit', $credential->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{ route('admin.credentials.destroy', $credential->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this credential?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                No credentials found. <a href="{{ route('admin.credentials.create') }}">Add the first one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($credentials->hasPages())
            <div class="pagination">
                {{ $credentials->links() }}
            </div>
        @endif
    </div>
@endsection