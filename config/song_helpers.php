<?php

function nln_song_cover_filename(?string $songCover, ?string $albumCover): ?string
{
    $songCover = trim((string) $songCover);
    if ($songCover !== '') {
        return $songCover;
    }

    $albumCover = trim((string) $albumCover);
    if ($albumCover !== '') {
        return $albumCover;
    }

    return null;
}

function nln_public_song_cover_path(?string $songCover, ?string $albumCover, string $fallback = 'assets/img/default.jpg'): string
{
    $filename = nln_song_cover_filename($songCover, $albumCover);
    if ($filename === null) {
        return $fallback;
    }

    $absolutePath = dirname(__DIR__) . '/public/assets/img/albums/' . $filename;
    if (!file_exists($absolutePath)) {
        return $fallback;
    }

    return 'assets/img/albums/' . $filename;
}

function nln_admin_song_cover_path(?string $songCover, ?string $albumCover, string $fallback = '../public/assets/img/default.jpg'): string
{
    $filename = nln_song_cover_filename($songCover, $albumCover);
    if ($filename === null) {
        return $fallback;
    }

    $absolutePath = dirname(__DIR__) . '/public/assets/img/albums/' . $filename;
    if (!file_exists($absolutePath)) {
        return $fallback;
    }

    return '../public/assets/img/albums/' . $filename;
}
