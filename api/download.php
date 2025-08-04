<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class TikTokDownloader {
    private $url;
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36';
    
    public function __construct($url) {
        $this->url = $url;
    }
    
    public function download() {
        // Validasi URL TikTok
        if (!$this->isValidTikTokUrl()) {
            return $this->errorResponse('URL TikTok tidak valid');
        }
        
        // Coba berbagai API secara berurutan
        $apis = [
            'ssstik' => 'downloadViaSsstik',
            'tikwm' => 'downloadViaTikwm',
            'tikmate' => 'downloadViaTikmate',
            'snaptik' => 'downloadViaSnaptik'
        ];
        
        foreach ($apis as $name => $method) {
            try {
                $result = $this->$method();
                if ($result) {
                    return $this->successResponse($result, $name);
                }
            } catch (Exception $e) {
                error_log("API {$name} failed: " . $e->getMessage());
                continue;
            }
        }
        
        return $this->errorResponse('Semua API gagal dihubungi');
    }
    
    private function downloadViaSsstik() {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://ssstik.io/api/ajaxSearch",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['q' => $this->url, 'lang' => 'en']),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'User-Agent: ' . $this->userAgent,
                'Referer: https://ssstik.io/',
                'Origin: https://ssstik.io',
                'Accept-Language: en-US,en;q=0.9'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (empty($data['links'][0]['href'])) {
            throw new Exception("Invalid API response format");
        }
        
        return [
            'no_watermark' => $data['links'][0]['href'],
            'hd' => $data['links'][1]['href'] ?? null,
            'music' => $data['music']['url'] ?? null,
            'cover' => $data['cover']['url'] ?? null,
            'author' => $data['author'] ?? null
        ];
    }
    
    private function downloadViaTikwm() {
        $apiUrl = "https://tikwm.com/api/?url=" . urlencode($this->url);
        $response = $this->makeRequest($apiUrl);
        $data = json_decode($response, true);
        
        if ($data['code'] != 0 || empty($data['data']['play'])) {
            throw new Exception("TikWM API error");
        }
        
        return [
            'no_watermark' => $data['data']['play'],
            'hd' => $data['data']['hdplay'] ?? $data['data']['play'],
            'music' => $data['data']['music']['play_url'] ?? null,
            'cover' => $data['data']['cover'],
            'author' => [
                'username' => $data['data']['author']['unique_id'] ?? null,
                'nickname' => $data['data']['author']['nickname'] ?? null
            ]
        ];
    }
    
    private function downloadViaTikmate() {
        $apiUrl = "https://api.tikmate.app/api/lookup?url=" . urlencode($this->url);
        $response = $this->makeRequest($apiUrl);
        $data = json_decode($response, true);
        
        if (empty($data['url'])) {
            throw new Exception("TikMate API error");
        }
        
        return [
            'no_watermark' => $data['url'],
            'hd' => $data['hd_url'] ?? $data['url'],
            'author' => [
                'username' => $data['username'] ?? null
            ]
        ];
    }
    
    private function downloadViaSnaptik() {
        $apiUrl = "https://snaptik.app/api/v1?url=" . urlencode($this->url);
        $response = $this->makeRequest($apiUrl);
        $data = json_decode($response, true);
        
        if (empty($data['data']['video_url'])) {
            throw new Exception("Snaptik API error");
        }
        
        return [
            'no_watermark' => $data['data']['video_url'],
            'hd' => $data['data']['hd_url'] ?? $data['data']['video_url'],
            'music' => $data['data']['music_url'] ?? null,
            'cover' => $data['data']['cover_url'] ?? null
        ];
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: ' . $this->userAgent,
                'Accept-Language: en-US,en;q=0.9'
            ]
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }
    
    private function isValidTikTokUrl() {
        return preg_match('/https?:\/\/(www\.|vm\.|vt\.)?tiktok\.com\//', $this->url);
    }
    
    private function successResponse($data, $apiUsed) {
        return json_encode([
            'status' => 'success',
            'api_used' => $apiUsed,
            'data' => $data,
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
    }
    
    private function errorResponse($message) {
        return json_encode([
            'status' => 'error',
            'message' => $message,
            'tip' => 'Coba lagi beberapa saat atau gunakan link yang berbeda',
            'timestamp' => time()
        ], JSON_PRETTY_PRINT);
    }
}

// Main Execution
try {
    if (!isset($_GET['url'])) {
        throw new Exception('Parameter URL diperlukan');
    }

    $downloader = new TikTokDownloader($_GET['url']);
    echo $downloader->download();
    
    // Log request (opsional)
    file_put_contents('downloads.log', 
        date('Y-m-d H:i:s') . " - " . $_SERVER['REMOTE_ADDR'] . " - " . $_GET['url'] . PHP_EOL, 
        FILE_APPEND
    );
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
