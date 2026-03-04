<?php
// 1. Khai báo đây là file JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// 2. Lấy từ khóa tìm kiếm (ví dụ: ?q=Binh%20Phuoc)
if (!isset($_GET['q'])) {
    echo json_encode(['error' => 'Không có từ khóa tìm kiếm']);
    exit;
}
$query = urlencode($_GET['q']);

// 3. Địa chỉ API của Nominatim
$url = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=5";

// 4. Khởi tạo cURL
$ch = curl_init();

// 5. Cấu hình cURL
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

// 6. !!! QUAN TRỌNG: Đặt User-Agent (Chính sách của OpenStreetMap)
curl_setopt($ch, CURLOPT_USERAGENT, 'SmartCity-Report-App (http://localhost/DOAN_CN)');

// 7. Thực thi
$result_json = curl_exec($ch);

// 8. Kiểm tra lỗi (nếu có)
if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// 9. Đóng cURL
curl_close($ch);

// 10. Trả kết quả (vốn đã là JSON) về cho JavaScript
echo $result_json;
?>