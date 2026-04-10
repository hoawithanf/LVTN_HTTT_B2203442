<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
$ch = curl_init('https://api.lyrics.ovh/v1/Taylor%20Swift/Tim%20McGraw');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$resp = curl_exec($ch);
$err = curl_error($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $http\n";
echo "ERR: $err\n";
echo "BODY:\n" . substr($resp,0,1000);
