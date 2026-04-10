<?php
header("Content-Type: application/json; charset=UTF-8");

include(__DIR__ . "/env_loader.php");
loadEnv(__DIR__ . "/../../.env");

include("database.php");
include("meaning_api_openai.php");

$song_id = isset($_GET["song_id"]) ? (int) $_GET["song_id"] : 0;
$force = isset($_GET["force"]) && $_GET["force"] === "1";

if (!$song_id) {
    echo json_encode(["success" => false, "error" => "Thiếu song_id"]);
    exit;
}

$q = $conn->prepare("
    SELECT s.title, s.artist_id, s.lyrics, s.meaning
    FROM songs s
    WHERE s.song_id = ?
");
$q->bind_param("i", $song_id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "Không tìm thấy bài hát."]);
    exit;
}

$song = $res->fetch_assoc();
$artistName = '';

if (!empty($song['artist_id'])) {
    $artistStmt = $conn->prepare("SELECT artist_name FROM artists WHERE artist_id = ?");
    $artistStmt->bind_param("i", $song['artist_id']);
    $artistStmt->execute();
    $artistName = $artistStmt->get_result()->fetch_assoc()['artist_name'] ?? '';
    $artistStmt->close();
}

if (!$force && !empty(trim((string) $song["meaning"]))) {
    echo json_encode([
        "success" => true,
        "cached" => true,
        "meaning" => nl2br(htmlspecialchars($song["meaning"]))
    ]);
    exit;
}

if (empty(trim((string) $song["lyrics"]))) {
    echo json_encode(["success" => false, "error" => "Lyrics trống, không thể phân tích."]);
    exit;
}

$analysis = analyzeSongMeaning_OpenAI($song["lyrics"], $song["title"] ?? '', $artistName);

if (!$analysis["success"]) {
    echo json_encode(["success" => false, "error" => $analysis["error"]]);
    exit;
}

$meaning = $analysis["meaning"];

$save = $conn->prepare("UPDATE songs SET meaning = ? WHERE song_id = ?");
$save->bind_param("si", $meaning, $song_id);
$save->execute();
$save->close();

echo json_encode([
    "success" => true,
    "cached" => false,
    "meaning" => nl2br(htmlspecialchars($meaning))
]);
