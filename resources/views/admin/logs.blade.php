@extends('layouts.admin')

@section('title', 'System Logs')
@section('page-title', 'System Logs')

@section('content')
    <style>
        .logs-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logs-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .logs-info span {
            padding: 5px 10px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filters-container {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select, .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-input {
            min-width: 250px;
        }
        
        .btn-filter {
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-filter:hover {
            background: #2980b9;
        }
        
        .btn-clear {
            padding: 8px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-clear:hover {
            background: #c0392b;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .logs-table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 14px;
            color: #495057;
        }
        
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        
        .logs-table tr:hover {
            background: #f8f9fa;
        }
        
        .log-level {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .log-level.ERROR, .log-level.CRITICAL, .log-level.ALERT, .log-level.EMERGENCY {
            background: #dc3545;
            color: white;
        }
        
        .log-level.WARNING {
            background: #ffc107;
            color: #000;
        }
        
        .log-level.INFO, .log-level.NOTICE {
            background: #17a2b8;
            color: white;
        }
        
        .log-level.DEBUG {
            background: #6c757d;
            color: white;
        }
        
        .log-timestamp {
            color: #6c757d;
            white-space: nowrap;
            font-size: 12px;
        }
        
        .log-message {
            max-width: 600px;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .log-message pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .expand-message {
            color: #3498db;
            cursor: pointer;
            font-size: 11px;
            text-decoration: underline;
            margin-top: 5px;
            display: inline-block;
        }
        
        .no-logs {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            gap: 5px;
        }
        
        .pagination .page-item {
            display: inline-block;
        }
        
        .pagination .page-link {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
            display: block;
        }
        
        .pagination .page-item.active .page-link {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>

    <div class="logs-container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="logs-header">
            <div class="logs-info">
                <span>📁 File Size: {{ $logFileSizeFormatted }}</span>
                <span>📝 Total Entries: {{ number_format($totalLines) }}</span>
            </div>
            
            <form action="{{ route('admin.logs.clear') }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all logs? A backup will be created.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-clear">🗑️ Clear Logs</button>
            </form>
        </div>

        <div class="filters-container">
            <form method="GET" action="{{ route('admin.logs') }}" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <select name="level" class="filter-select">
                    <option value="all" {{ request('level') == 'all' || !request('level') ? 'selected' : '' }}>All Levels</option>
                    <option value="emergency" {{ request('level') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    <option value="alert" {{ request('level') == 'alert' ? 'selected' : '' }}>Alert</option>
                    <option value="critical" {{ request('level') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="error" {{ request('level') == 'error' ? 'selected' : '' }}>Error</option>
                    <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="notice" {{ request('level') == 'notice' ? 'selected' : '' }}>Notice</option>
                    <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="debug" {{ request('level') == 'debug' ? 'selected' : '' }}>Debug</option>
                </select>
                
                <input type="text" name="search" class="search-input" placeholder="Search in logs..." value="{{ request('search') }}">
                
                <button type="submit" class="btn-filter">🔍 Filter</button>
                
                @if(request('level') || request('search'))
                    <a href="{{ route('admin.logs') }}" class="btn-filter" style="background: #95a5a6; text-decoration: none;">✖ Clear Filters</a>
                @endif
            </form>
        </div>

        @if($pagination->count() > 0)
            <table class="logs-table">
                <thead>
                    <tr>
                        <th width="150">Timestamp</th>
                        <th width="100">Level</th>
                        <th>Message</th>
                        <th width="120">Time Ago</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pagination as $log)
                        <tr>
                            <td class="log-timestamp">{{ $log['timestamp'] }}</td>
                            <td>
                                <span class="log-level {{ $log['level'] }}">{{ $log['level'] }}</span>
                            </td>
                            <td class="log-message">
                                @if($log['truncated'])
                                    <div class="message-preview">
                                        <pre>{{ $log['message'] }}</pre>
                                        <span class="expand-message" onclick="toggleMessage(this)" data-full-message="{{ base64_encode($log['full_message'] ?? '') }}">Show full message</span>
                                    </div>
                                @else
                                    <pre>{{ $log['message'] }}</pre>
                                @endif
                            </td>
                            <td class="log-timestamp">{{ $log['formatted_time'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-container">
                {{ $pagination->appends(request()->query())->links() }}
            </div>
        @else
            <div class="no-logs">
                <p>📭 No log entries found</p>
                @if(request('level') || request('search'))
                    <p style="margin-top: 10px;">Try adjusting your filters</p>
                @endif
            </div>
        @endif
    </div>

    <script>
        function toggleMessage(element) {
            const preview = element.parentElement;
            const fullMessage = atob(element.dataset.fullMessage);
            const preElement = preview.querySelector('pre');
            
            if (element.textContent === 'Show full message') {
                preElement.textContent = fullMessage;
                element.textContent = 'Show less';
            } else {
                preElement.textContent = fullMessage.substring(0, 500) + '...';
                element.textContent = 'Show full message';
            }
        }
        
        // Auto-refresh every 30 seconds (optional)
        // setTimeout(function() {
        //     location.reload();
        // }, 30000);
    </script>
@endsection