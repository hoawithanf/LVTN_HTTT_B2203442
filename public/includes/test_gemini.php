<?php
include("env_loader.php");
loadEnv(__DIR__ . "/../../.env");

$apiKey = getenv("GEMINI_API_KEY");

echo "<b>Testing Gemini...</b><br>";

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=$apiKey";

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Phân tích ý nghĩa bài hát 'Love Story' của Taylor Swift bằng tiếng Việt."]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "CURL ERROR: $error";
    exit;
}

echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
