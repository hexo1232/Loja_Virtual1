<?php
function enviarParaCloudinary($file_path) {
    // Lê as variáveis que você configurou no painel do Render
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey    = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');

    $timestamp = time();
    // Gera a assinatura de segurança exigida pela Cloudinary
    $signature = sha1("timestamp=$timestamp" . $apiSecret);

    $url = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    $data = [
        'file'      => new CURLFile($file_path),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    // Retorna a URL segura da imagem ou false se falhar
    return $result['secure_url'] ?? false;
}
?>