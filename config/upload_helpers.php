<?php

function nln_allowed_image_extensions(): array
{
    return ['jpg', 'jpeg', 'png', 'webp'];
}

function nln_allowed_image_mime_types(): array
{
    return ['image/jpeg', 'image/png', 'image/webp'];
}

function nln_ensure_directory(string $directory): bool
{
    if (is_dir($directory)) {
        return true;
    }

    return mkdir($directory, 0755, true);
}

function nln_upload_image(array $file, string $targetDirectory, string $prefix = 'img', int $maxBytes = 5242880): array
{
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Tải ảnh thất bại.'];
    }

    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        return ['success' => false, 'filename' => null, 'error' => 'Ảnh vượt quá dung lượng cho phép.'];
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, nln_allowed_image_extensions(), true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Định dạng ảnh không hợp lệ.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpName) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mime === false || !in_array($mime, nln_allowed_image_mime_types(), true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Loại ảnh không được hỗ trợ.'];
    }

    if (!nln_ensure_directory($targetDirectory)) {
        return ['success' => false, 'filename' => null, 'error' => 'Không thể tạo thư mục lưu ảnh.'];
    }

    try {
        $filename = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(8)), $extension);
    } catch (Throwable $e) {
        $filename = sprintf('%s_%s.%s', $prefix, uniqid(), $extension);
    }

    $destination = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Không thể lưu ảnh tải lên.'];
    }

    return ['success' => true, 'filename' => $filename, 'error' => null];
}

function nln_save_base64_image(string $dataUri, string $targetDirectory, string $prefix = 'img', int $maxBytes = 5242880): array
{
    if (trim($dataUri) === '') {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if (!preg_match('#^data:(image/(png|jpeg|webp));base64,(.+)$#', $dataUri, $matches)) {
        return ['success' => false, 'filename' => null, 'error' => 'Dữ liệu ảnh không hợp lệ.'];
    }

    $mime = $matches[1];
    $payload = str_replace(' ', '+', $matches[3]);
    $binary = base64_decode($payload, true);

    if ($binary === false || strlen($binary) === 0 || strlen($binary) > $maxBytes) {
        return ['success' => false, 'filename' => null, 'error' => 'Ảnh đại diện không hợp lệ hoặc quá lớn.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo ? finfo_buffer($finfo, $binary) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($detectedMime === false || $detectedMime !== $mime || !in_array($mime, nln_allowed_image_mime_types(), true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Loại ảnh đại diện không được hỗ trợ.'];
    }

    if (!nln_ensure_directory($targetDirectory)) {
        return ['success' => false, 'filename' => null, 'error' => 'Không thể tạo thư mục avatar.'];
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    try {
        $filename = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(8)), $extensionMap[$mime]);
    } catch (Throwable $e) {
        $filename = sprintf('%s_%s.%s', $prefix, uniqid(), $extensionMap[$mime]);
    }

    $destination = rtrim($targetDirectory, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (file_put_contents($destination, $binary) === false) {
        return ['success' => false, 'filename' => null, 'error' => 'Không thể lưu ảnh đại diện.'];
    }

    return ['success' => true, 'filename' => $filename, 'error' => null];
}
