<?php
// Detect BASE PATH automatically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            ? "https://" : "http://";

$host = $_SERVER['HTTP_HOST'];

$folder = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\");

// Example result: http://localhost/NLN_PROJECT/public
$BASE_URL = $protocol . $host . $folder . "/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <meta name="description" content="Musicalisation - Lyrics, meaning và trải nghiệm âm nhạc cá nhân hóa" />
    <meta name="author" content="Musicalisation Team" />
    <title>Musicalisation</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $BASE_URL ?>assets/favicon.ico" />

    <!-- Font Awesome -->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet" />

    <!-- Core CSS -->
    <link href="<?= $BASE_URL ?>css/styles.css" rel="stylesheet" />
    <link href="<?= $BASE_URL ?>css/ui-index.css" rel="stylesheet" />

</head>
<body>
