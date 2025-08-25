<?php
// VLC-style HLS proxy
// Usage: proxy.php?url=<encoded_url>

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate URL
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "Error: No URL provided.";
    exit();
}

$url = $_GET['url'];

// Only allow valid URLs
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "Error: Invalid URL.";
    exit();
}

// Detect if it’s an HLS playlist
$isPlaylist = stripos($url, ".m3u8") !== false;

// Minimal headers to mimic VLC
$headers = [
    'User-Agent: VLC/3.0.18 LibVLC/3.0.18',
    'Accept: */*',
    'Connection: keep-alive'
];

// cURL setup
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, $isPlaylist); // true for playlist, false for segments
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Stream HLS segments directly
if (!$isPlaylist) {
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
        echo $data;
        flush();
        return strlen($data);
    });
}

// Fetch response
$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo "cURL error: " . curl_error($ch);
    curl_close($ch);
    exit();
}

curl_close($ch);

// If it’s a playlist, rewrite relative URLs to proxy URLs
if ($isPlaylist) {
    header("Content-Type: application/vnd.apple.mpegurl");
    $lines = explode("\n", $response);
    $output = [];
    $baseUrl = dirname($url);
    $proxyBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                 . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === "" || strpos($trimmed, "#") === 0) {
            $output[] = $trimmed;
        } else {
            // Make relative URLs absolute
            if (strpos($trimmed, "http") !== 0) {
                $trimmed = rtrim($baseUrl, '/') . '/' . ltrim($trimmed, '/');
            }
            // Rewrite to proxy
            $output[] = $proxyBase . "?url=" . urlencode($trimmed);
        }
    }
    echo implode("\n", $output);
}
?>
