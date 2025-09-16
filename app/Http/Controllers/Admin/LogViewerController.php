<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LogViewerController extends Controller
{
    protected $logPath;
    
    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }
    
    /**
     * Display the log viewer interface
     */
    public function index()
    {
        $files = $this->getLogFiles();
        return view('admin.logs.index', compact('files'));
    }
    
    /**
     * Get log content via API
     */
    public function getLogs(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $level = $request->get('level', 'all');
        $search = $request->get('search', '');
        $lines = $request->get('lines', 100);
        
        $filename = "laravel-{$date}.log";
        $filepath = $this->logPath . '/' . $filename;
        
        if (!File::exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found for date: ' . $date,
                'logs' => [],
                'stats' => $this->getEmptyStats()
            ]);
        }
        
        $logs = $this->parseLogFile($filepath, $level, $search, $lines);
        $stats = $this->getLogStats($logs);
        
        return response()->json([
            'success' => true,
            'logs' => $logs,
            'stats' => $stats,
            'file' => $filename,
            'date' => $date
        ]);
    }
    
    /**
     * Download log file
     */
    public function download(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $filename = "laravel-{$date}.log";
        $filepath = $this->logPath . '/' . $filename;
        
        if (!File::exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found'
            ], 404);
        }
        
        return response()->download($filepath, $filename);
    }
    
    /**
     * Clear log file
     */
    public function clear(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $filename = "laravel-{$date}.log";
        $filepath = $this->logPath . '/' . $filename;
        
        if (File::exists($filepath)) {
            File::put($filepath, '');
            
            return response()->json([
                'success' => true,
                'message' => 'Log file cleared successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Log file not found'
        ], 404);
    }
    
    /**
     * Get system information
     */
    public function systemInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'environment' => config('app.env'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'database_driver' => config('database.default'),
            'log_channel' => config('logging.default'),
            'disk_free_space' => $this->formatBytes(disk_free_space('/')),
            'disk_total_space' => $this->formatBytes(disk_total_space('/')),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
        
        return response()->json([
            'success' => true,
            'info' => $info
        ]);
    }
    
    /**
     * Get list of log files
     */
    protected function getLogFiles()
    {
        $files = [];
        $logFiles = File::files($this->logPath);
        
        foreach ($logFiles as $file) {
            if (strpos($file->getFilename(), 'laravel-') === 0 && strpos($file->getFilename(), '.log') !== false) {
                $date = str_replace(['laravel-', '.log'], '', $file->getFilename());
                $files[] = [
                    'filename' => $file->getFilename(),
                    'date' => $date,
                    'size' => $this->formatBytes($file->getSize()),
                    'last_modified' => Carbon::createFromTimestamp($file->getMTime())->diffForHumans()
                ];
            }
        }
        
        // Sort by date descending
        usort($files, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        return $files;
    }
    
    /**
     * Parse log file content
     */
    protected function parseLogFile($filepath, $level = 'all', $search = '', $lines = 100)
    {
        $logs = [];
        $content = File::get($filepath);
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*?)(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/s';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $logEntry = [
                'timestamp' => $match[1],
                'environment' => $match[2],
                'level' => strtolower($match[3]),
                'message' => trim($match[4]),
                'context' => []
            ];
            
            // Parse context/stack trace if present
            if (strpos($logEntry['message'], '{') !== false) {
                $contextStart = strpos($logEntry['message'], '{');
                $context = substr($logEntry['message'], $contextStart);
                $logEntry['message'] = substr($logEntry['message'], 0, $contextStart);
                
                // Try to decode JSON context
                $decoded = json_decode($context, true);
                if ($decoded) {
                    $logEntry['context'] = $decoded;
                }
            }
            
            // Apply filters
            if ($level !== 'all' && $logEntry['level'] !== $level) {
                continue;
            }
            
            if ($search && stripos($logEntry['message'], $search) === false) {
                continue;
            }
            
            $logs[] = $logEntry;
        }
        
        // Sort by timestamp descending
        usort($logs, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        
        // Limit number of logs
        return array_slice($logs, 0, $lines);
    }
    
    /**
     * Get log statistics
     */
    protected function getLogStats($logs)
    {
        $stats = [
            'total' => count($logs),
            'levels' => [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
                'debug' => 0
            ]
        ];
        
        foreach ($logs as $log) {
            if (isset($stats['levels'][$log['level']])) {
                $stats['levels'][$log['level']]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get empty stats structure
     */
    protected function getEmptyStats()
    {
        return [
            'total' => 0,
            'levels' => [
                'emergency' => 0,
                'alert' => 0,
                'critical' => 0,
                'error' => 0,
                'warning' => 0,
                'notice' => 0,
                'info' => 0,
                'debug' => 0
            ]
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}