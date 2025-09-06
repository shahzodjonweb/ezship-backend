@extends('layouts.admin')

@section('title', 'Edit Credential')
@section('page-title', 'Edit Credential')

@section('content')
    <div class="card">
        <div class="card-header">
            Edit Credential: {{ $credential->name }}
        </div>
        
        <form action="{{ route('admin.credentials.update', $credential->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="name">Credential Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $credential->name) }}" required>
                @error('name')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="refresh_token">Refresh Token</label>
                <textarea id="refresh_token" name="refresh_token" rows="4" placeholder="Enter refresh token...">{{ old('refresh_token', $credential->refresh_token) }}</textarea>
                @error('refresh_token')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="access_token">Access Token</label>
                <textarea id="access_token" name="access_token" rows="4" placeholder="Enter access token (optional)...">{{ old('access_token', $credential->access_token) }}</textarea>
                @error('access_token')
                    <small style="color: red;">{{ $message }}</small>
                @enderror
            </div>
            
            <div class="form-group">
                <label>Created At</label>
                <input type="text" value="{{ $credential->created_at->format('Y-m-d H:i:s') }}" disabled>
            </div>
            
            <div class="form-group">
                <label>Last Updated</label>
                <input type="text" value="{{ $credential->updated_at->format('Y-m-d H:i:s') }}" disabled>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">Update Credential</button>
                <a href="{{ route('admin.credentials') }}" class="btn btn-danger">Cancel</a>
            </div>
        </form>
    </div>
@endsection