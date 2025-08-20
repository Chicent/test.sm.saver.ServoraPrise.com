<?php
header('Content-Type: application/json');

// Check if URL is provided
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No URL provided'
    ]);
    exit;
}

$url = trim($_POST['url']);

// Simple validation
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid URL'
    ]);
    exit;
}

// Example response (in real case, you'd use API calls or scraping)
$response = [
    'status' => 'success',
    'data' => [
        'download_url' => $url,   // just echo back URL for now
        'title' => 'Downloaded Content',
        'type' => 'video'
    ]
];

echo json_encode($response);
exit;
?>