<?php
// api/analyze_image.php - PHIÊN BẢN PRO MAX
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    // API Key của mày
    $apiKey = "AIzaSyABfjtBGDG-mfrFv4vc1JtJGyE2yAsh8nk"; 

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload ảnh.');
    }

    $tmpFilePath = $_FILES['image']['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    if (strpos($mimeType, 'image/') !== 0) {
        throw new Exception('File không phải là ảnh.');
    }

    $imageData = base64_encode(file_get_contents($tmpFilePath));
    
    // Dùng model 2.0 Flash cho nhanh và thông minh
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

    // === CÂU LỆNH THẦN THÁNH (PROMPT) ===
    // Dạy AI luật chơi: ID nào ứng với lỗi gì. Bắt nó chọn 1 cái duy nhất.
    $promptText = "
    You are an AI assistant for a Smart City issue reporting system. 
    Analyze the image and classify the main problem into ONE of the following IDs:
    
    - ID 1: Road damage, pothole, broken asphalt, crack in street.
    - ID 2: Broken street light, dark lamp post.
    - ID 3: Trash, garbage, litter, waste dump, plastic bags.
    - ID 4: Fallen tree, broken branch blocking road, uprooted tree. (Note: Ignore normal standing trees).
    - ID 5: Fire, smoke, burning.
    
    Rules:
    - If multiple issues exist, pick the most severe/obvious one.
    - If no obvious problem (just a normal street/tree), return ID null.
    - Return ONLY a valid JSON object. Do not format as markdown. Format:
    {\"id\": <number or null>, \"reason\": \"<short explanation>\"}
    ";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText],
                    [
                        "inline_data" => [
                            "mime_type" => $mimeType,
                            "data" => $imageData
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Lỗi API Google ($httpCode)");
    }

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Làm sạch chuỗi JSON (đôi khi AI thêm ```json ở đầu)
        $rawText = str_replace(['```json', '```'], '', $rawText);
        $aiData = json_decode(trim($rawText), true);

        if (json_last_error() === JSON_ERROR_NONE && isset($aiData['id'])) {
            echo json_encode([
                'success' => true, 
                'suggested_id' => $aiData['id'], // Trả về thẳng ID (1, 2, 3...)
                'reason' => $aiData['reason']
            ]);
        } else {
            // Trường hợp AI trả lời lung tung, fallback về null
            echo json_encode(['success' => true, 'suggested_id' => null]);
        }
    } else {
        throw new Exception('AI không phản hồi.');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>