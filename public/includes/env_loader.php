<?php
function loadEnv($path)
{
    if (!file_exists($path)) {
        // Debug khi cần
        // echo "ENV file not found: $path";
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') continue;

        list($key, $value) = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}
