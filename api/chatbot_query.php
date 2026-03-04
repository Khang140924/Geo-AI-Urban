<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

// =================================================================
// 1. API KEY CỦA BẠN (Giữ nguyên)
// =================================================================
$GEMINI_API_KEY = 'AIzaSyBIeqTUzKMGFL1XUfg0qOyrsYn1pu_vFa4'; 

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['reply' => 'Bạn cần đăng nhập.']));
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$message = strtolower(trim($_POST['message'] ?? '')); 
$reply_message = ""; 

// =================================================================
// 2. BLOCK ƯU TIÊN: CÂU HỎI CỦA ADMIN (ĐÃ THÊM 4 CÂU HỎI MỚI)
// =================================================================
if (strtolower($user_role) == 'admin') {
    try {
        
        // ==========================================================
        // MỚI: KIỂM TRA BÁO CÁO THEO ID (ví dụ: "check #45")
        // Dùng regex để tìm số ID
        // ==========================================================
        if (preg_match('/(báo cáo|check|kiểm tra|report) #?(\d+)/', $message, $matches)) {
            $report_id = (int)$matches[2]; // Lấy con số (ví dụ: 45)
            $stmt = $pdo->prepare("SELECT status, description FROM reports WHERE id = :id");
            $stmt->execute([':id' => $report_id]);
            $report = $stmt->fetch();
            
            if ($report) {
                $reply_message = "Báo cáo #$report_id (" . htmlspecialchars(substr($report['description'], 0, 50)) . "...) có trạng thái: " . htmlspecialchars($report['status']);
            } else {
                $reply_message = "Không tìm thấy báo cáo nào có ID #$report_id.";
            }
        }
        
        // ==========================================================
        // MỚI: TÌM NGƯỜI DÙNG TÍCH CỰC NHẤT
        // ==========================================================
        elseif (strpos($message, 'nhiều nhất') !== false || strpos($message, 'top 1') !== false || strpos($message, 'tích cực') !== false) {
            $stmt = $pdo->query("SELECT TOP 1 u.fullname, COUNT(r.id) as report_count 
                                 FROM reports r 
                                 JOIN users u ON r.user_id = u.id 
                                 GROUP BY u.fullname 
                                 ORDER BY report_count DESC");
            $user = $stmt->fetch();
            if ($user) {
                $reply_message = "Người dùng tích cực nhất là " . htmlspecialchars($user['fullname']) . " với " . $user['report_count'] . " báo cáo.";
            } else {
                $reply_message = "Chưa có dữ liệu về người dùng.";
            }
        }

        // TÌM VẤN ĐỀ PHỔ BIẾN NHẤT
        elseif (strpos($message, 'phổ biến nhất') !== false || strpos($message, 'danh mục nào') !== false) {
            $stmt = $pdo->query("SELECT TOP 1 c.name, COUNT(r.id) as report_count 
                                 FROM reports r 
                                 JOIN categories c ON r.category_id = c.id 
                                 GROUP BY c.name 
                                 ORDER BY report_count DESC");
            $category = $stmt->fetch();
            if ($category) {
                $reply_message = "Vấn đề phổ biến nhất là '" . htmlspecialchars($category['name']) . "' với " . $category['report_count'] . " báo cáo.";
            } else {
                $reply_message = "Chưa có dữ liệu về danh mục.";
            }
        }

        // ĐẾM BÁO CÁO TRONG NGÀY
        elseif (strpos($message, 'hôm nay') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE created_at >= CAST(GETDATE() AS DATE)");
            $count = $stmt->fetchColumn();
            $reply_message = "Hôm nay có $count báo cáo mới được gửi.";
        }

        // CÁC CÂU ĐẾM CŨ
        elseif (strpos($message, 'chờ duyệt') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = N'Chờ duyệt'");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có $count báo cáo 'Chờ duyệt'.";
        } 
        elseif (strpos($message, 'mới') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = N'Mới'");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có $count báo cáo 'Mới'.";
        }
        elseif (strpos($message, 'đã duyệt') !== false || strpos($message, 'được duyệt') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status IN (N'Mới', N'Đang xử lý', N'Đã hoàn thành')");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có $count báo cáo đã được duyệt (bao gồm 'Mới', 'Đang xử lý', và 'Đã hoàn thành').";
        } 
        elseif (strpos($message, 'hoàn thành') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = N'Đã hoàn thành'");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có $count báo cáo 'Đã hoàn thành'.";
        }
        elseif (strpos($message, 'đang xử lý') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = N'Đang xử lý'");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có $count báo cáo 'Đang xử lý'.";
        }
        elseif (strpos($message, 'tổng') !== false || strpos($message, 'tất cả') !== false) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM reports");
            $count = $stmt->fetchColumn();
            $reply_message = "Chào Admin. Hiện có tổng cộng $count báo cáo trong hệ thống.";
        }

    } catch (Exception $e) {
        $reply_message = "Lỗi CSDL khi truy vấn Admin: " . $e->getMessage();
    }
}

// =================================================================
// 3. BLOCK CŨ: CÂU HỎI CỦA USER (Chỉ chạy nếu Admin không hỏi gì)
// =================================================================
if (empty($reply_message)) {
    if (strpos($message, 'cách gửi') !== false || strpos($message, 'hướng dẫn') !== false) {
        $reply_message = "Để gửi báo cáo: Nhấn nút 'Bạn thấy sự cố gì' > Điền thông tin > Gửi.";
    } elseif (strpos($message, 'trạng thái') !== false || strpos($message, 'báo cáo') !== false) {
        try {
            $stmt = $pdo->prepare("SELECT TOP 1 description, status FROM reports WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $user_id]);
            $last = $stmt->fetch();
            $reply_message = $last ? "Báo cáo mới nhất của bạn: " . htmlspecialchars($last['description']) . " - Trạng thái: " . htmlspecialchars($last['status']) : "Bạn chưa có báo cáo nào.";
        } catch (Exception $e) { $reply_message = "Lỗi kết nối CSDL."; }
    } elseif (strpos($message, 'tổng hợp') !== false || strpos($message, 'liệt kê') !== false || strpos($message, 'tất cả') !== false) {
        try {
            $stmt_all = $pdo->prepare("SELECT TOP 5 description, status FROM reports WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt_all->execute([':user_id' => $user_id]);
            $reports = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

            if (count($reports) > 0) {
                $reply_message = "Đây là 5 báo cáo gần nhất của bạn:\n";
                $i = 1;
                foreach ($reports as $report) {
                    $reply_message .= $i . ". " . htmlspecialchars($report['description']) . " (Trạng thái: " . htmlspecialchars($report['status']) . ")\n";
                    $i++;
                }
            } else {
                $reply_message = "Bạn chưa gửi báo cáo nào để tôi tổng hợp.";
            }
        } catch (Exception $e) { 
            $reply_message = "Lỗi CSDL: Không thể tra cứu danh sách báo cáo."; 
        }
    } elseif (strpos($message, 'chào') !== false || strpos($message, 'hello') !== false) {
        $reply_message = "Chào bạn! Tôi là AI hỗ trợ.";
    }
}

// =================================================================
// 4. GỌI GOOGLE GEMINI (CHẾ ĐỘ THÔNG MINH) - KHÔNG THAY ĐỔI
// (Chỉ chạy nếu không có từ khóa nào ở trên khớp)
// =================================================================
if (empty($reply_message)) {

    // --- CẤU HÌNH MỚI: DÙNG GEMINI 2.5 FLASH (Thay vì 1.5) ---
    // Đã thay đổi 'gemini-1.5-flash' thành 'gemini-2.5-flash'
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $GEMINI_API_KEY;

    $parts = [];
    if (!empty($message)) {
        $parts[] = ['text' => $message];
    } else {
        $parts[] = ['text' => "Mô tả hình ảnh này."];
    }

    if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] == 0) {
        $imageData = base64_encode(file_get_contents($_FILES['chat_image']['tmp_name']));
        $parts[] = [
            'inlineData' => [
                'mimeType' => $_FILES['chat_image']['type'],
                'data' => $imageData
            ]
        ];
    }
    
    $data = ['contents' => [['parts' => $parts]]];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $result = json_decode($response);
        $reply_message = $result->candidates[0]->content->parts[0]->text ?? "AI không phản hồi (Empty Response).";
    } else {
        $errObj = json_decode($response);
        $errMsg = $errObj->error->message ?? $response;
        // Gợi ý debug nếu vẫn lỗi
        $reply_message = "Lỗi kết nối ($httpCode): $errMsg";
    }
}

echo json_encode(['reply' => $reply_message], JSON_UNESCAPED_UNICODE);


// =================================================================
// 5. HÀM TÌM MODEL (KHÔNG THAY ĐỔI)
// =================================================================
function getValidModel($apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    $preferred = [
        'gemini-1.5-flash',
        'gemini-1.5-flash-001',
        'gemini-1.5-flash-latest',
        'gemini-1.5-pro',
        'gemini-pro'
    ];

    if (isset($data['models'])) {
        foreach ($preferred as $pref) {
            foreach ($data['models'] as $model) {
                if (strpos($model['name'], $pref) !== false && in_array("generateContent", $model['supportedGenerationMethods'])) {
                    return $model['name']; 
                }
            }
        }
        foreach ($data['models'] as $model) {
            if (in_array("generateContent", $model['supportedGenerationMethods'])) {
                return $model['name'];
            }
        }
    }
    
    return 'models/gemini-2.5-flash';
}
?>