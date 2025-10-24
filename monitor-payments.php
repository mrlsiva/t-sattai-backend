#!/usr/bin/env php
<?php
// Real-time log monitoring script
// Run with: php monitor-payments.php

echo "🔍 Payment Process Monitor Started\n";
echo "Watching Laravel logs for payment-related activity...\n";
echo "Press Ctrl+C to stop\n\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Log file not found: $logFile\n";
    exit(1);
}

$lastSize = filesize($logFile);

while (true) {
    clearstatcache();
    $currentSize = filesize($logFile);
    
    if ($currentSize > $lastSize) {
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastSize);
        
        while (($line = fgets($handle)) !== false) {
            // Filter for payment-related logs
            if (strpos($line, 'Payment') !== false || 
                strpos($line, 'payment') !== false || 
                strpos($line, 'Stripe') !== false ||
                strpos($line, 'stripe') !== false) {
                
                $timestamp = date('H:i:s');
                
                // Color coding for different log levels
                if (strpos($line, 'ERROR') !== false) {
                    echo "\033[31m[$timestamp] 🚨 $line\033[0m";
                } elseif (strpos($line, 'WARNING') !== false) {
                    echo "\033[33m[$timestamp] ⚠️  $line\033[0m";
                } elseif (strpos($line, 'INFO') !== false) {
                    echo "\033[32m[$timestamp] ℹ️  $line\033[0m";
                } else {
                    echo "[$timestamp] $line";
                }
            }
        }
        
        fclose($handle);
        $lastSize = $currentSize;
    }
    
    usleep(500000); // Check every 0.5 seconds
}
?>