<?php
include(__DIR__ . '/session.php');
include(__DIR__ . '/database.php');
require_once __DIR__ . '/../../config/song_helpers.php';
require_once __DIR__ . '/../../config/recommendation_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'unauthorized',
    ]);
    exit;
}

function nln_profile_rec_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';

$recommendationData = nln_profile_recommendations_fresh($conn, $userId, 6, $forceRefresh);
$recommendItems = $recommendationData['items'] ?? [];
$recommendSummary = trim((string) ($recommendationData['summary'] ?? ''));

ob_start();

if ($recommendItems && $recommendSummary !== '') {
    ?>
    <div class="profile-note-box">
        <?= nln_profile_rec_h($recommendSummary) ?>
    </div>
    <?php
}

if (!empty($recommendItems)) {
    ?>
    <div class="profile-recommend-list">
        <?php foreach ($recommendItems as $r): ?>
            <?php
            $img = $r['cover'] ?? nln_public_song_cover_path($r['song_cover'] ?? null, $r['album_cover'] ?? null);
            ?>
            <a href="post.php?id=<?= (int) $r['song_id'] ?>" class="profile-recommend-card">
                <img
                    src="<?= nln_profile_rec_h($img) ?>"
                    alt="<?= nln_profile_rec_h($r['title']) ?>"
                    class="profile-recommend-cover"
                >
                <div class="profile-recommend-copy">
                    <div class="profile-recommend-title"><?= nln_profile_rec_h($r['title']) ?></div>
                    <div class="profile-recommend-artist"><?= nln_profile_rec_h($r['artist_name']) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
} else {
    ?>
    <div class="content-empty-state content-empty-state-compact">
        <i class="fas fa-headphones"></i>
        <h3>Chua du du lieu goi y</h3>
        <p>Hay tiep tuc tim kiem va tuong tac voi noi dung de he thong dua ra goi y chinh xac hon.</p>
    </div>
    <?php
}

$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html,
    'summary' => $recommendSummary,
    'count' => count($recommendItems),
]);
