<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['url'])) {
    echo json_encode(['error' => 'URL parameter is required']);
    exit;
}

$url = $_GET['url'];

// Validasi URL TikTok
if (!preg_match('/https?:\/\/(www\.|vm\.|vt\.)?tiktok\.com\//', $url)) {
    echo json_encode(['error' => 'Invalid TikTok URL']);
    exit;
}

try {
    // Gunakan API pihak ketiga (contoh: tikwm.com)
    $apiUrl = "https://tikwm.com/api/?url=" . urlencode($url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('API Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('API returned HTTP code ' . $httpCode);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        throw new Exception('Invalid API response');
    }
    
    // Format response
    $result = [
        'no_watermark' => $data['data']['play'],
        'hd' => $data['data']['hdplay'] ?? $data['data']['play'],
        'cover' => $data['data']['cover'],
        'music' => $data['data']['music']['play_url'] ?? null
    ];
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to download video: ' . $e->getMessage()]);
}
?>
