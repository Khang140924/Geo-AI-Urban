<?php
header('Content-Type: application/json');

// DÁN API KEY CỦA MÀY VÀO ĐÂY
$apiKey = 'AIzaSyDLWM7nmx09C3q-RuNlHGR8e7h8kIdD87E'; 

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<h1>Danh sách các Model AI mày được dùng:</h1>";
echo "<pre>";
if(isset($data['models'])) {
    foreach($data['models'] as $model) {
        // Chỉ hiện những con nào hỗ trợ nhìn ảnh (generateContent)
        if(in_array("generateContent", $model['supportedGenerationMethods'])) {
            echo "Tên model: " . $model['name'] . "\n";
            echo "--------------------------------\n";
        }
    }
} else {
    print_r($data);
}
echo "</pre>";
?>