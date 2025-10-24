<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

Route::get('/', function () {
    return view('welcome');
});

// Cache clearing route for production (remove after use)
Route::get('/clear', function () {
    try {
        $output = [];
        
        // Clear application cache
        Artisan::call('cache:clear');
        $output[] = '✅ Application cache cleared';
        
        // Clear configuration cache
        Artisan::call('config:clear');
        $output[] = '✅ Configuration cache cleared';
        
        // Clear route cache
        Artisan::call('route:clear');
        $output[] = '✅ Route cache cleared';
        
        // Clear view cache
        Artisan::call('view:clear');
        $output[] = '✅ View cache cleared';
        
        // Clear compiled services
        Artisan::call('clear-compiled');
        $output[] = '✅ Compiled services cleared';
        
        // Clear event cache
        Artisan::call('event:clear');
        $output[] = '✅ Event cache cleared';
        
        // Clear schedule cache (if exists)
        try {
            Artisan::call('schedule:clear-cache');
            $output[] = '✅ Schedule cache cleared';
        } catch (Exception $e) {
            $output[] = '⚠️ Schedule cache: ' . $e->getMessage();
        }
        
        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $output[] = '✅ OPcache cleared';
        } else {
            $output[] = '⚠️ OPcache not available';
        }
        
        // Optimize for production
        Artisan::call('config:cache');
        $output[] = '✅ Configuration cached for production';
        
        Artisan::call('route:cache');
        $output[] = '✅ Routes cached for production';
        
        $output[] = '';
        $output[] = '🎉 All caches cleared and optimized!';
        $output[] = 'Timestamp: ' . now()->format('Y-m-d H:i:s T');
        
        return response('<html><head><title>Cache Cleared</title><style>body{font-family:Arial;margin:40px;background:#f5f5f5;} .container{background:white;padding:30px;border-radius:8px;} .success{color:#28a745;} .warning{color:#ffc107;}</style></head><body><div class="container"><h1>🧹 Cache Clearing Complete</h1><pre>' . implode("\n", $output) . '</pre><br><div style="background:#fff3cd;padding:15px;border-radius:4px;margin:20px 0;"><strong>⚠️ Security Notice:</strong><br>Remove this /clear route from production for security!</div><a href="/" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;">← Back to Home</a></div></body></html>')
            ->header('Content-Type', 'text/html');
            
    } catch (Exception $e) {
        return response('<html><head><title>Cache Clear Error</title><style>body{font-family:Arial;margin:40px;background:#f5f5f5;} .container{background:white;padding:30px;border-radius:8px;} .error{color:#dc3545;}</style></head><body><div class="container"><h1 class="error">❌ Cache Clear Failed</h1><p><strong>Error:</strong> ' . $e->getMessage() . '</p><p><strong>File:</strong> ' . $e->getFile() . '</p><p><strong>Line:</strong> ' . $e->getLine() . '</p><a href="/" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;">← Back to Home</a></div></body></html>')
            ->header('Content-Type', 'text/html');
    }
});
