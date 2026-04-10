<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';

/* ================= CONFIG ================= */
$type = $_GET['type'] ?? 'pdf'; // pdf | csv
$date = date('Y-m-d_H-i-s');

/* ================= LOAD DATA ================= */

// USERS
$users = $conn->query("
    SELECT username, email, role, created_at
    FROM users
");

// ARTISTS
$artists = $conn->query("
    SELECT artist_name, country, birth_year, created_at
    FROM artists
");

// SONGS
$songs = $conn->query("
    SELECT s.title, a.artist_name, s.language, s.release_date
    FROM songs s
    JOIN artists a ON s.artist_id = a.artist_id
");

// SEARCH LOGS
$searchLogs = $conn->query("
    SELECT song_title, artist_name, search_time
    FROM search_logs
    ORDER BY search_time DESC
    LIMIT 100
");

// TOP SONGS
$topSongs = $conn->query("
    SELECT s.title, a.artist_name, COUNT(sl.log_id) total
    FROM search_logs sl
    JOIN songs s ON s.song_id = sl.song_id
    JOIN artists a ON a.artist_id = s.artist_id
    GROUP BY s.song_id
    ORDER BY total DESC
    LIMIT 10
");

/* ================= EXPORT CSV ================= */
/* ⚠️ KHÔNG ĐỤNG LOGIC CSV CŨ – CHỈ BỔ SUNG */
if ($type === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=report_$date.csv");

    $out = fopen('php://output', 'w');

    fputcsv($out, ['USERS']);
    fputcsv($out, ['Username', 'Email', 'Role', 'Created At']);
    while ($u = $users->fetch_assoc()) {
        fputcsv($out, [$u['username'], $u['email'], $u['role'], $u['created_at']]);
    }

    fputcsv($out, []);
    fputcsv($out, ['ARTISTS']);
    fputcsv($out, ['Artist Name', 'Country', 'Birth Year', 'Created At']);
    while ($a = $artists->fetch_assoc()) {
        fputcsv($out, [$a['artist_name'], $a['country'], $a['birth_year'], $a['created_at']]);
    }

    fputcsv($out, []);
    fputcsv($out, ['SONGS']);
    fputcsv($out, ['Title', 'Artist', 'Language', 'Release Date']);
    while ($s = $songs->fetch_assoc()) {
        fputcsv($out, [$s['title'], $s['artist_name'], $s['language'], $s['release_date']]);
    }

    fputcsv($out, []);
    fputcsv($out, ['TOP SONGS']);
    fputcsv($out, ['Title', 'Artist', 'Search Count']);
    while ($t = $topSongs->fetch_assoc()) {
        fputcsv($out, [$t['title'], $t['artist_name'], $t['total']]);
    }

    fclose($out);
    exit;
}

/* ================= EXPORT PDF ================= */
require_once __DIR__ . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_top' => 15,
    'margin_bottom' => 15,
]);

$mpdf->SetTitle('NLN Lyrics - Admin Report');

$html = '
<style>
body { font-family: sans-serif; font-size: 11px; }
h1 { text-align: center; }
h2 { background:#f2f2f2; padding:6px; margin-top:20px; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
th, td { border:1px solid #ccc; padding:6px; }
th { background:#eee; }
</style>

<h1>NLN Lyrics – Admin Report</h1>
<p><strong>Generated at:</strong> '.$date.'</p>

<h2>Users</h2>
<table>
<tr><th>Username</th><th>Email</th><th>Role</th><th>Created</th></tr>';

$users->data_seek(0);
while ($u = $users->fetch_assoc()) {
    $html .= "<tr>
        <td>{$u['username']}</td>
        <td>{$u['email']}</td>
        <td>{$u['role']}</td>
        <td>{$u['created_at']}</td>
    </tr>";
}

$html .= '</table><h2>Artists</h2><table>
<tr><th>Name</th><th>Country</th><th>Birth Year</th><th>Created</th></tr>';

$artists->data_seek(0);
while ($a = $artists->fetch_assoc()) {
    $html .= "<tr>
        <td>{$a['artist_name']}</td>
        <td>{$a['country']}</td>
        <td>{$a['birth_year']}</td>
        <td>{$a['created_at']}</td>
    </tr>";
}

$html .= '</table><h2>Top Songs</h2><table>
<tr><th>Title</th><th>Artist</th><th>Search Count</th></tr>';

while ($t = $topSongs->fetch_assoc()) {
    $html .= "<tr>
        <td>{$t['title']}</td>
        <td>{$t['artist_name']}</td>
        <td>{$t['total']}</td>
    </tr>";
}

$html .= '</table>';

$mpdf->WriteHTML($html);
$mpdf->Output('NLN_Report_'.$date.'.pdf', 'D');
exit;
