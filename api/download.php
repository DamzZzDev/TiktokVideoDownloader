<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Fungsi utama
function downloadTikTokVideo($url) {
    // Validasi URL TikTok
    if (!preg_match('/https?:\/\/(www\.|vm\.|vt\.)?tiktok\.com\//', $url)) {
        return ['error' => 'Invalid TikTok URL. Contoh URL valid: https://vm.tiktok.com/abcdef/'];
    }

    // Step 1: Ambil parameter video dari SSSTik
    $ssstikUrl = "https://ssstik.io/abc?url=" . urlencode($url);
    $paramResult = getContents($ssstikUrl);
    
    // Ekstrak token dari response
    if (!preg_match('/token:\s*\'([^\']+)\'/', $paramResult, $tokenMatches)) {
        return ['error' => 'Failed to extract token from SSSTik'];
    }
    $token = $tokenMatches[1];

    // Step 2: Kirim request download ke API SSSTik
    $apiUrl = "https://ssstik.io/abc?url=dl";
    $postData = [
        'id' => $url,
        'locale' => 'en',
        'tt' => $token,
        'ts' => time()
    ];

    $apiResponse = getContents($apiUrl, $postData);
    $data = json_decode($apiResponse, true);

    // Validasi response
    if (!$data || isset($data['error'])) {
        return ['error' => $data['error'] ?? 'Invalid response from SSSTik API'];
    }

    // Format response
    return [
        'status' => 'success',
        'no_watermark' => $data['video']['url'] ?? null,
        'hd' => $data['video']['hd'] ?? null,
        'music' => $data['music']['url'] ?? null,
        'cover' => $data['cover']['url'] ?? null,
        'author' => [
            'username' => $data['author']['unique_id'] ?? null,
            'nickname' => $data['author']['nickname'] ?? null
        ]
    ];
}

// Fungsi helper untuk HTTP request
function getContents($url, $postData = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Referer: https://ssstik.io/',
            'Origin: https://ssstik.io'
        ]
    ]);

    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    return $result;
}

// Eksekusi
try {
    if (!isset($_GET['url'])) {
        throw new Exception('Parameter URL diperlukan');
    }

    $url = $_GET['url'];
    $result = downloadTikTokVideo($url);
    
    // Simpan log download (opsional)
    file_put_contents('downloads.log', date('Y-m-d H:i:s') . " - " . $url . PHP_EOL, FILE_APPEND);
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'tip' => 'Coba lagi beberapa saat atau gunakan link yang berbeda'
    ]);
}
?>
