<?php
/**
 * APP_KEY Generator for cPanel (No SSH Required)
 * Upload this file to your cPanel and visit it in browser
 * Then delete this file after getting your key
 */

// Generate random 32-byte key
$key = base64_encode(random_bytes(32));
$appKey = 'base64:' . $key;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Laravel APP_KEY Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .key-box { 
            background: #f8f9fa; 
            padding: 20px; 
            border: 1px solid #dee2e6; 
            border-radius: 5px;
            margin: 20px 0;
        }
        .copy-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>🔑 Laravel APP_KEY Generator</h1>
    <p>Your new APP_KEY has been generated:</p>
    
    <div class="key-box">
        <strong>APP_KEY=</strong>
        <span id="appKey"><?php echo $appKey; ?></span>
        <br><br>
        <button class="copy-btn" onclick="copyToClipboard()">📋 Copy Key</button>
    </div>
    
    <h3>How to use:</h3>
    <ol>
        <li>Copy the key above</li>
        <li>Open your <code>.env</code> file in cPanel File Manager</li>
        <li>Find the line <code>APP_KEY=</code></li>
        <li>Replace it with: <code>APP_KEY=<?php echo $appKey; ?></code></li>
        <li>Save the file</li>
        <li><strong>Delete this generate-app-key.php file immediately for security!</strong></li>
    </ol>
    
    <script>
        function copyToClipboard() {
            const text = document.getElementById('appKey').textContent;
            navigator.clipboard.writeText(text).then(function() {
                alert('APP_KEY copied to clipboard!');
            });
        }
    </script>
    
    <div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin-top: 30px;">
        <strong>⚠️ Security Warning:</strong> Delete this file immediately after copying your key!
    </div>
</body>
</html>