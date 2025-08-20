<?php
header('Content-Type: application/json');

// Get the "url" from query string (?url=...)
if (!isset($_GET['url']) || empty($_GET['url'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No URL provided'
    ]);
    exit;
}

$url = trim($_GET['url']);

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid URL'
    ]);
    exit;
}

// For now, just return the input URL as a fake "download"
$response = [
    'status' => 'success',
    'data' => [
        'download_url' => $url,
        'title' => 'Downloaded Content',
        'type' => 'video'
    ]
];

echo json_encode($response);
exit;