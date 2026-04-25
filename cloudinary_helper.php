<?php
function enviarParaCloudinary($file_path) {
    // Tenta as três formas de ler variáveis de ambiente no Render
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? $_SERVER['CLOUDINARY_CLOUD_NAME'] ?? null);
    $apiKey    = getenv('CLOUDINARY_API_KEY')    ?: ($_ENV['CLOUDINARY_API_KEY']    ?? $_SERVER['CLOUDINARY_API_KEY']    ?? null);
    $apiSecret = getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? $_SERVER['CLOUDINARY_API_SECRET'] ?? null);

    // Valida variáveis e ficheiro
    if (!$cloudName || !$apiKey || !$apiSecret) {
        error_log("CLOUDINARY: variáveis de ambiente não encontradas.");
        return false;
    }

    $realPath = realpath($file_path);
    if (!$realPath || !file_exists($realPath) || filesize($realPath) === 0) {
        error_log("CLOUDINARY: ficheiro inválido ou vazio — $file_path");
        return false;
    }

    $timestamp = time();
    $signature = sha1("timestamp=$timestamp" . $apiSecret);
    $url       = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    $data = [
        'file'      => new CURLFile($realPath),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $data);
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("CLOUDINARY curl error: $curlError");
        return false;
    }

    $result = json_decode($response, true);

    if (!isset($result['secure_url'])) {
        error_log("CLOUDINARY resposta inesperada: $response");
        return false;
    }

    return $result['secure_url'];
}
?>