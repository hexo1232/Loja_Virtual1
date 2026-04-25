<?php
$cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'NAO ENCONTRADO');
$apiKey    = getenv('CLOUDINARY_API_KEY')    ?: ($_ENV['CLOUDINARY_API_KEY']    ?? 'NAO ENCONTRADO');
$apiSecret = getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? 'NAO ENCONTRADO');

echo "Cloud Name: $cloudName<br>";
echo "API Key: $apiKey<br>";
echo "Secret: " . (strlen($apiSecret) > 3 ? 'OK (' . strlen($apiSecret) . ' chars)' : 'NAO ENCONTRADO');