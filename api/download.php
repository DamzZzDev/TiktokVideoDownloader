<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function downloadTikTok($url) {
    // Validasi URL
    if (!preg_match('/tiktok\.com\//', $url)) {
        return ['error' => 'URL TikTok tidak valid'];
    }

    // Coba berbagai endpoint
    $endpoints = [
        [
            'url' => 'https://api16-normal-c-useast1a.tiktokv.com/aweme/v1/feed/?aweme_id='.extractVideoId($url),
            'method' => 'GET'
        ],
        [
            'url' => 'https://tikwm.com/api/?url='.urlencode($url),
            'method' => 'GET'
        ],
        [
            'url' => 'https://www.tikdown.org/getAjax?url='.urlencode($url),
            'method' => 'POST'
        ]
    ];

    foreach ($endpoints as $endpoint) {
        try {
            $result = makeRequest($endpoint['url'], $endpoint['method']);
            $data = json_decode($result, true);
            
            if ($data && isset($data['video']['download_addr']['url_list'][0])) {
                return [
                    'success' => true,
                    'video_url' => $data['video']['download_addr']['url_list'][0],
                    'api_used' => $endpoint['url']
                ];
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return ['error' => 'Semua API gagal dihubungi'];
}

function extractVideoId($url) {
    preg_match('/video\/(\d+)/', $url, $matches);
    return $matches[1] ?? '';
}

function makeRequest($url, $method = 'GET') {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    return $response;
}

// Eksekusi
if (isset($_GET['url'])) {
    $result = downloadTikTok($_GET['url']);
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'Parameter URL diperlukan'], JSON_PRETTY_PRINT);
}
?>
