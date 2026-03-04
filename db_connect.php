<?php
// BẮT BUỘC THÊM CÁC DÒNG NÀY ĐỂ DEBUG TRÊN LOCALHOST
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// ... (các dòng kết nối database của bạn tiếp tục ở đây) ...
// Bật session ở file kết nối để tất cả các file khác đều có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ----- THAY ĐỔI THÔNG TIN KẾT NỐI CỦA BẠN -----
$serverName = "localhost,1433"; // Ví dụ: "LAPTOP-123\SQLEXPRESS"
$dbName = "smartcity_db";
$username = "sa"; // Hoặc tài khoản login SQL Server của bạn
$password = "Lamkhang140924";
// ----------------------------------------------

try {
    // Sử dụng driver SQLSRV
    $pdo = new PDO("sqlsrv:server=$serverName;Database=$dbName", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>