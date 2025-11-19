<?php
// Tắt báo lỗi PHP để tránh hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

// Bắt đầu bộ đệm
ob_start();
require_once '../config/config.php';
ob_clean(); // Xóa sạch rác

header('Content-Type: application/json; charset=utf-8');

// 1. API KEY
$apiKey = "NHAP API KEY"; 
$apiKey = trim($apiKey);

// --- HÀM 1: TỰ ĐỘNG LẤY MODEL HỢP LỆ ---
function getValidModel($key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    // Ưu tiên tìm model 'flash' (nhanh) hoặc 'pro' (thông minh)
    if (isset($data['models'])) {
        foreach ($data['models'] as $model) {
            // Tìm model có hỗ trợ chat (generateContent) và ưu tiên bản 1.5 Flash
            if (in_array("generateContent", $model['supportedGenerationMethods'])) {
                if (strpos($model['name'], 'flash') !== false) {
                    return $model['name']; // Trả về ví dụ: "models/gemini-1.5-flash"
                }
            }
        }
        // Nếu không tìm thấy flash, lấy cái đầu tiên hỗ trợ chat
        foreach ($data['models'] as $model) {
            if (in_array("generateContent", $model['supportedGenerationMethods'])) {
                return $model['name'];
            }
        }
    }
    return false;
}

try {
    // Kiểm tra input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Sai phương thức.');
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = isset($input['message']) ? trim($input['message']) : '';
    if (empty($userMessage)) throw new Exception('Bạn chưa nhập câu hỏi.');

    // BƯỚC 1: TỰ ĐỘNG TÌM MODEL
    $modelName = getValidModel($apiKey);
    
    if (!$modelName) {
        throw new Exception("Không tìm thấy Model nào khả dụng với Key này.");
    }

    // BƯỚC 2: GỬI TIN NHẮN VỚI MODEL VỪA TÌM ĐƯỢC
    // Lưu ý: $modelName đã chứa sẵn chữ "models/..." nên ta không thêm vào URL nữa
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/$modelName:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Bạn là trợ lý đầu bếp Master Chef. Trả lời ngắn gọn (dưới 100 từ), vui vẻ. Câu hỏi: " . $userMessage]
                ]
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) throw new Exception('Lỗi mạng: ' . $curlError);

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = isset($result['error']['message']) ? $result['error']['message'] : 'Lỗi lạ';
        throw new Exception("Google lỗi ($httpCode): $msg");
    }

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $botReply = $result['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['reply' => nl2br(htmlspecialchars($botReply))]);
    } else {
        echo json_encode(['reply' => 'Xin lỗi, mình không trả lời được câu này.']);
    }

} catch (Exception $e) {
    echo json_encode(['reply' => 'Hệ thống: ' . $e->getMessage()]);
}
?>