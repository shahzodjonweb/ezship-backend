@extends('layouts.admin')

@section('title', 'Add Credential')
@section('page-title', 'Add New Credential')

@section('content')
    <div class="card">
        <div class="card-header">
            Create New Credential
        </div>
        
        <form action="{{ route('admin.credentials.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="name">Credential Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., quickbooks, stripe, etc." required>
                @error('name')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="refresh_token">Refresh Token</label>
                <textarea id="refresh_token" name="refresh_token" rows="4" placeholder="Enter refresh token...">{{ old('refresh_token') }}</textarea>
                @error('refresh_token')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="access_token">Access Token</label>
                <textarea id="access_token" name="access_token" rows="4" placeholder="Enter access token (optional)...">{{ old('access_token') }}</textarea>
                @error('access_token')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Create Credential</button>
                <a href="{{ route('admin.credentials') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
@endsection