<?php
function enviarParaCloudinary($file) {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey    = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');

    $timestamp = time();
    $signature = sha1("timestamp=$timestamp" . $apiSecret);
    $url = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    // Garante que o ficheiro existe e tem conteúdo
    if (!file_exists($file) || filesize($file) === 0) return false;

    $data = [
        'file'      => new CURLFile(realpath($file)),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['secure_url'] ?? false;
}
?>