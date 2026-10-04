<?php
$ch = curl_init('http://127.0.0.1:8000/api/platform/users?page=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Status: {$code}\n";
echo "Body: " . substr($res, 0, 300) . "\n";
