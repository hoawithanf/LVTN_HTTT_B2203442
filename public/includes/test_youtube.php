<?php
require_once __DIR__ . '/../includes/env_loader.php';

loadEnv(__DIR__ . '/../.env');

$apiKey = getenv('YOUTUBE_API_KEY');

echo "<pre>";
echo "API KEY: ";
var_dump($apiKey);
echo "</pre>";

if (!$apiKey) {
    die("❌ Không load được API KEY từ .env");
}

$url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
    'part'             => 'snippet',
    'q'                => 'Taylor Swift Love Story',
    'type'             => 'video',
    'videoEmbeddable'  => 'true',
    'maxResults'       => 1,
    'key'              => $apiKey
]);

$response = file_get_contents($url);

echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";
