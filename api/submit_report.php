<?php
header('Content-Type: application/json'); // Trả về dạng JSON
require_once '../db_connect.php'; // Đã có session_start()

// 1. Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để gửi báo cáo.']);
    exit;
}

// 2. Kiểm tra dữ liệu
if (empty($_POST['category_id']) || empty($_POST['description']) || empty($_POST['latitude']) || empty($_POST['longitude'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']);
    exit;
}
if (!isset($_FILES['report_image']) || $_FILES['report_image']['error'] != 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Vui lòng tải lên ảnh sự cố.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$category_id = $_POST['category_id'];
$description = $_POST['description'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$image_url = null;

// 3. Xử lý upload ảnh
$uploadDir = '../assets/uploads/';
// Tạo tên file duy nhất để tránh trùng lặp
$fileName = uniqid() . '-' . basename($_FILES['report_image']['name']);
$targetPath = $uploadDir . $fileName;
$imageType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

// Kiểm tra định dạng ảnh
if (in_array($imageType, ['jpg', 'jpeg', 'png', 'gif'])) {
    $api_user = '278420346';
    $api_secret = 'UVw6xP3kWz5bpiWgpVyPw7uYFmB7bAN2';

    // 2. Chuẩn bị dữ liệu gửi đi
    $params = array(
        'media' => new CURLFile($_FILES['report_image']['tmp_name']),
        'models' => 'nudity,wad,gore', // nudity: khỏa thân, wad: vũ khí/rượu/thuốc, gore: máu me
        'api_user' => $api_user,
        'api_secret' => $api_secret
    );

    // 3. Gọi API bằng cURL
    $ch = curl_init('https://api.sightengine.com/1.0/check.json');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    curl_close($ch);

    // 4. Phân tích kết quả trả về
    $output = json_decode($response, true);

    if (isset($output['status']) && $output['status'] == 'success') {
        
        // A. Kiểm tra Khỏa thân (Nudity)
        // safe < 0.5 nghĩa là ảnh không an toàn
        // raw > 0.8 nghĩa là ảnh quá trần trụi
        if ($output['nudity']['safe'] < 0.5 || $output['nudity']['raw'] > 0.8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'AI phát hiện ảnh có nội dung nhạy cảm (18+). Vui lòng chọn ảnh khác.']);
            exit;
        }

        // B. Kiểm tra Vũ khí (Weapon)
        if ($output['weapon'] > 0.8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'AI phát hiện hình ảnh vũ khí. Báo cáo bị từ chối.']);
            exit;
        }

        // C. Kiểm tra Máu me (Gore)
        if ($output['gore']['prob'] > 0.8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ảnh chứa nội dung bạo lực/máu me.']);
            exit;
        }
        
    } else {
        // Nếu API lỗi hoặc hết lượt miễn phí thì có thể cho qua (hoặc chặn tùy mày)
        // error_log("Lỗi Sightengine: " . print_r($output, true));
    }
    if (move_uploaded_file($_FILES['report_image']['tmp_name'], $targetPath)) {
        // Lưu đường dẫn tương đối vào CSDL
        $image_url = 'assets/uploads/' . $fileName;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu ảnh.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif).']);
    exit;
}

// 4. Lưu vào CSDL
try {
    $query = "
        INSERT INTO reports (user_id, category_id, description, latitude, longitude, image_url, status)
       VALUES (:user_id, :category_id, :description, :latitude, :longitude, :image_url, N'Chờ duyệt')
    ";
    
    $stmt = $pdo->prepare($query);
    
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':image_url', $image_url);
    
    $stmt->execute();
    try {
        $notif_msg = "Bài viết của bạn đã được gửi, hãy chờ duyệt";
        // Lưu ý: GETDATE() là hàm lấy giờ của SQL Server. Nếu dùng MySQL thì đổi thành NOW()
        $sql_notif = "INSERT INTO notifications (user_id, message, link, is_read, created_at) 
                      VALUES (:uid, :msg, '#', 0, GETDATE())"; 
        
        $stmt_notif = $pdo->prepare($sql_notif);
        $stmt_notif->bindParam(':uid', $user_id);
        $stmt_notif->bindParam(':msg', $notif_msg); // Nội dung thông báo
        $stmt_notif->execute();
    } catch (Exception $e) {
        // Lỗi tạo thông báo thì bỏ qua, không làm ảnh hưởng quy trình chính
    }
    
    // 5. Trả về thành công
    echo json_encode(['success' => true, 'message' => 'Gửi báo cáo thành công!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>