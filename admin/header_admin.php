<?php 
// 1. Kết nối CSDL (chú ý đường dẫn ../)
require_once __DIR__ . '/../db_connect.php';
// 2. Kiểm tra quyền Admin (Cực kỳ quan trọng)
require_once 'security_check.php'; 

// Lấy tên file hiện tại (ví dụ: 'index.php' hoặc 'stats.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* 1. CẤU TRÚC TỔNG THỂ */
        body { 
            /* === NỀN MÀU XANH BẠC HÀ === */
            background-color: #f4f9f6ff ;            
            
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
        }
        
        /* DÁN CODE NÀY ĐÈ LÊN CLASS .top-navbar CŨ TRONG HEADER_ADMIN.PHP */

.top-navbar {
        /* Thay đổi quan trọng nhất ở đây: */
        position: relative; /* Cố định vị trí, không chạy theo khi cuộn */
        
        z-index: 1030;
        /* Giữ nguyên màu xanh đẹp mắt */
        background: linear-gradient(135deg, #20c997 0%, #00A78E 100%);
        /* Giữ nguyên bóng đổ */
        box-shadow: 0 4px 15px rgba(0, 167, 142, 0.25);
        padding: 0.8rem 2rem;
        display: flex;
        align-items: center;
    }

    /* Logo / Tên Dashboard */
    .top-navbar .navbar-brand {
        font-weight: 800;
        font-size: 1.4rem;
        color: #ffffff !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .top-navbar .navbar-brand i {
        background-color: rgba(255,255,255,0.2);
        padding: 8px;
        border-radius: 8px;
        font-size: 1rem;
    }

    /* Phần User bên phải */
    .top-navbar .nav-link {
        color: #ffffff !important;
        font-weight: 600;
        display: flex;
        align-items: center;
        background-color: rgba(255, 255, 255, 0.15);
        padding: 6px 15px !important;
        border-radius: 30px;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .top-navbar .nav-link:hover {
        background-color: #ffffff;
        color: #00A78E !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .nav-avatar-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid #ffffff;
        margin-right: 10px;
        object-fit: cover;
    }
/* === CSS MỚI CHO CHỮ TRÊN BANNER (ĐÃ CHỈNH LẠI) === */
/* === CSS CHO CHỮ TRÊN BANNER (Nhỏ hơn) === */
.banner-text-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); 
    color: white;
    
    /* 1. THAY ĐỔI: Giảm cỡ chữ nhỏ hơn nữa */
    font-size: 2.2rem; /* (Trước đây là 2.5rem) */
    
    font-weight: 700; 
    text-align: center;
    line-height: 1.3;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.9); 
    z-index: 5; 
    width: 100%; 
}
/* === XÓA / ẨN CÁC CHỮ VÀ BIỂU TƯỢNG TRÊN NAVBAR === */
/* Chúng ta phải ẩn/xóa để banner hiển thị rõ */

/* Để hiện avatar Admin và nút dropdown, chúng ta sẽ phải sắp xếp lại HTML */
/* Hiện tại, chúng ta sẽ ẩn nó đi để banner được ưu tiên */
.top-navbar .navbar-nav {
    display: none !important; 
}

/* Đảm bảo nội dung trang không bị banner che khuất */

/* Thêm một lớp phủ mờ để chữ "ADMIN" màu trắng nổi bật hơn */


/* Đảm bảo nội dung navbar (chữ) nằm trên lớp phủ */
.top-navbar .container-fluid {
    position: relative;
    z-index: 2;
}

        /* 2. SIDEBAR (Menu bên trái) */
        .sidebar {
            position: sticky; /* <-- THÊM DÒNG NÀY */
            top: 0;           /* <-- THÊM DÒNG NÀY */
            float: left; /* <-- THÊM DÒNG NÀY */
            height: 100vh; /* <-- THÊM DÒNG NÀY */
            width: 250px;
            padding: 1.5rem 1rem;
            background-color: #fafbfbff;
            color: #343a40;
            border-right: 1px solid #dee2e6;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: #555;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link .fas {
            width: 20px;
            margin-right: 10px;
            color: #888;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link.active, 
        .sidebar .nav-link:hover {
            color: #00A78E;
            background-color: #e6f7ef;
            border-radius: 0.375rem;
        }
        .sidebar .nav-link.active .fas,
        .sidebar .nav-link:hover .fas {
             color: #00A78E;
        }
        .sidebar .navbar-brand, .sidebar hr {
             display: none; /* Ẩn các phần thừa */
        }
        /* Footer của Sidebar (Khối User) */
        .sidebar-footer {
            margin-top: auto; /* Đẩy xuống dưới cùng */
        }
        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid #dee2e6;
        }
        .sidebar-user-toggle {
            color: #333;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        .sidebar-user-toggle:hover {
            background-color: #f0f0f0;
        }
        .sidebar-user-toggle .nav-avatar-img {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
        }


        /* 3. KHU VỰC NỘI DUNG CHÍNH (Nâng cấp) */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            overflow: hidden;
        }
        
        .page-card {
            background-color: #ffffffff;
            border-radius: 0.5rem; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            padding: 1.5rem 2rem; 
            margin-bottom: 1.5rem;
        }
        
        /* 4. GIAO DIỆN BẢNG (Table) MỚI */
        .main-table {
            border-collapse: collapse; 
            background-color: #ffffff;
        }
        
        /* Header của bảng (Màu xanh nổi bật) */
        .main-table th {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background-color: #00A78E; 
            color: #ffffffff;
            font-weight: 600;
        }
        .main-table thead th:first-child {
            border-top-left-radius: 0.375rem;
        }
        .main-table thead th:last-child {
            border-top-right-radius: 0.375rem;
        }

        /* Hàng và Ô (cell) của bảng */
        .main-table tbody tr {
            transition: background-color 0.2s ease;
        }
        .main-table td {
            padding: 1rem 1.25rem; 
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef; /* Đường kẻ ngang mờ */
            border-top: 0;
        }
        .main-table tbody tr:last-child td {
            border-bottom: 0;
        }
        .main-table .img-thumbnail {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 0.375rem;
            padding: 0;
            border: none;
        }

        /* 5. TRẠNG THÁI (STATUS BADGES) */
        .badge-status {
    padding: 0.5em 0.9em; 
    font-size: 0.8rem;
    font-weight: 600; 
    display: inline-flex;
    align-items: center;
    border-radius: 50px; 
    
    text-transform: none !important; /* <--- DÒNG QUAN TRỌNG NHẤT: Thêm !important để chắc chắn tắt viết hoa */
    
    letter-spacing: normal;
    line-height: 1;
    white-space: nowrap; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.05); 
    transition: all 0.2s ease;
}

.badge-status i { margin-right: 5px; }

/* Các màu sắc giữ nguyên */
.badge-status.status-moi { background-color: #fff7e6; color: #ffa117; border: 1px solid #FFEDD5; }
.badge-status.status-dang-xu-ly { background-color: #e6f7ff; color: #0d6efd; border: 1px solid #DBEAFE; }
.badge-status.status-da-hoan-thanh { background-color: #e6f7ef; color: #00A78E; border: 1px solid #D1FAE5; }
.badge-status.status-cho-duyet { background-color: #f0f0f0; color: #555; border: 1px solid #E5E7EB; }
.badge-status.status-khong-hop-le { background-color: #fde7e9; color: #dc3545; border: 1px solid #FEE2E2; }

        /* 6. NÚT HÀNH ĐỘNG (ACTION BUTTONS) */
        .action-buttons .btn {
            border-radius: 50% !important;
            width: 35px;
            height: 35px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            margin: 0 3px;
            transition: all 0.2s ease;
        }
        .action-buttons .btn-update {
            background-color: #e6f7ef; color: #00A78E;
        }
        .action-buttons .btn-update:hover {
            background-color: #cff0e6; transform: scale(1.1);
        }
        .action-buttons .btn-delete {
            background-color: #fde7e9; color: #dc3545;
        }
        .action-buttons .btn-delete:hover {
            background-color: #fbd0d5; transform: scale(1.1);
        }
        .action-buttons .dropdown-toggle::after {
            display: none; 
        }
        
        /* 7. BỘ LỌC (ACCORDION) */
        .accordion-button {
            background-color: #ffffff; /* Nền trắng khi đóng */
            color: #343a40; /* Chữ đen */
            font-weight: 600;
            border-bottom: 1px solid #eef2f6; 
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 .25rem rgba(0, 167, 142, 0.25);
            border-color: #00A78E;
        }
        /* Khi mở ra (active) */
        .accordion-button:not(.collapsed) {
             background-color: #00A78E; /* Nền xanh mòng két */
             color: #ffffff; /* Chữ trắng */
             box-shadow: none;
        }
        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
        /* ======================================= */
/* CSS TÙY CHỈNH CHO MODAL CHI TIẾT BÁO CÁO */
/* ======================================= */

/* 1. Tùy chỉnh chung cho Modal */
#detailModal .modal-body {
    background-color: #f8f9fa; /* Nền xám rất nhạt cho dễ chịu mắt */
}

/* 2. Tiêu đề chính (ví dụ: "Cây đổ") */
#detail-category {
    font-size: 2.25rem; /* 36px - Làm cho nó thật nổi bật */
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1rem;
}

/* 3. Khối thông tin (Người gửi, Trạng thái...) */
#detail-content .col-md-7 > .mb-3:first-child p {
    font-size: 1.05rem; /* 17px */
    margin-bottom: 0.75rem; /* Thêm khoảng cách */
}
/* Làm cho nhãn (label) mờ đi, nội dung nổi lên */
#detail-content .col-md-7 > .mb-3:first-child p strong {
    color: #6c757d; /* Màu xám */
    font-weight: 500;
    display: inline-block;
    min-width: 90px; /* Giúp các mục thẳng hàng */
}
/* Riêng phần mô tả */
#detail-desc {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap; /* Giúp giữ lại các dấu xuống dòng */
}

/* 4. Tiêu đề các mục nhỏ (Hình ảnh, Vị trí, Lịch sử...) */
#detail-content h5.border-bottom {
    font-size: 1.3rem; /* 21px */
    font-weight: 600;
    color: #0056b3; /* Màu xanh chủ đạo */
    margin-top: 1.5rem; /* Tách biệt khỏi nội dung bên trên */
    padding-bottom: 0.6rem;
    margin-bottom: 1rem;
}

/* 5. Hình ảnh báo cáo */
#detail-image {
    border-radius: 8px !important; /* Bo góc đẹp hơn */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Thêm bóng đổ */
    border: none !important; /* Bỏ border mặc định */
}


/* 6. LỊCH SỬ PHÂN CÔNG (Timeline) */
.report-timeline {
    position: relative;
    padding-left: 30px; /* Không gian cho đường kẻ và icon */
}

/* Đường kẻ dọc của timeline */
.report-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 5px;
    bottom: 5px;
    width: 3px;
    background-color: #e9ecef;
    border-radius: 2px;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-icon {
    position: absolute;
    left: -32px; /* Đẩy icon ra ngoài */
    top: 0;
    width: 24px;
    height: 24px;
    background-color: #fff;
    border: 3px solid #007bff; /* Icon màu xanh */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #007bff;
    box-shadow: 0 0 0 3px #fff; /* Tạo viền trắng tách biệt */
}

.timeline-content {
    font-size: 0.95rem;
}
.timeline-content strong {
    color: #333;
}
.timeline-user {
    color: #6c757d;
    font-style: italic;
}

/* Ghi chú (note) */
.timeline-note {
    background-color: #e9ecef;
    padding: 0.5rem 0.75rem;
    border-radius: 5px;
    margin-top: 0.5rem;
    font-style: italic;
    border-left: 3px solid #007bff;
}

.timeline-time {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* 7. BÌNH LUẬN (Style kiểu chat) */
.comment-item {
    display: flex;
    margin-bottom: 1.25rem;
}

.comment-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-right: 12px;
    object-fit: cover;
    border: 2px solid #ddd;
}

.comment-content {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    flex-grow: 1; /* Để nó co giãn hết */
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
/* Bo góc "bong bóng chat" */
.comment-content.comment-user {
    border-top-left-radius: 0;
}

.comment-user-name {
    font-weight: 700;
    color: #0056b3;
}

.comment-text {
    margin-top: 0.25rem;
    color: #333;
    line-height: 1.5;
}

.comment-time {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.3rem;
}

/* 8. Style cho các Trạng Thái (Status Badges) */
/* Thêm các style này để badge trong modal đồng bộ với bên ngoài */
.badge-status {
    font-size: 0.9em !important;
    font-weight: 600 !important;
    padding: 0.6em 0.8em !important;
    border-radius: 0.25rem !important; /* Dùng pill-radius nếu bạn thích */
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-moi { color: #004085; background-color: #cce5ff; border: 1px solid #b8daff; }
.status-dang-xu-ly { color: #383d41; background-color: #e2e3e5; border: 1px solid #d6d8db; }
.status-da-hoan-thanh { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
.status-cho-duyet { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; }
.status-khong-hop-le { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
/* 1. Làm cho cột bên phải (Lịch sử, Bình luận) nổi bật hơn */
#detail-content .col-md-5 {
    background-color: #fff; /* Nền trắng (nếu modal-body là xám) */
    border-left: 1px solid #dee2e6; /* Đường kẻ phân chia 2 cột */
    padding: 1.5rem;
    border-radius: 0 0.5rem 0.5rem 0; /* Bo góc bên phải */
}

/* 2. Định dạng khối thông tin chi tiết (Người gửi, Ngày gửi...) */
/* Chúng ta sẽ dùng flex để căn chỉnh */
#detail-content .col-md-7 > .mb-3:first-child p {
    display: flex; /* Bật flexbox */
    font-size: 1rem; /* Chỉnh lại font size */
    margin-bottom: 0.6rem; /* Giảm khoảng cách */
    border-bottom: 1px dashed #e0e0e0; /* Thêm đường kẻ mờ */
    padding-bottom: 0.6rem;
}
#detail-content .col-md-7 > .mb-3:first-child p:last-child {
    border-bottom: none; /* Bỏ đường kẻ ở mục cuối */
}

/* Nhãn (label) */
#detail-content .col-md-7 > .mb-3:first-child p strong {
    font-weight: 600; /* Đậm hơn 1 chút */
    color: #343a40; /* Màu chữ chính */
    min-width: 100px; /* Đặt chiều rộng cố định */
    margin-right: 1rem; /* Khoảng cách giữa nhãn và nội dung */
}

/* Nội dung (value) */
#detail-content .col-md-7 > .mb-3:first-child p span {
    color: #555;
    flex-grow: 1; /* Để nội dung tự co giãn */
}

/* 3. Style cho các thông báo "Chưa có..." */
#detail-history-list p.text-muted,
#detail-comment-list p.text-muted {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 1rem;
    border-radius: 5px;
    text-align: center;
    font-style: normal !important; /* Bỏ in nghiêng */
}

/* 4. Điều chỉnh lại tiêu đề "Cây đổ" */
#detail-category {
    font-size: 1.8rem; /* Giảm size 1 chút (36px -> 29px) */
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6; /* Thêm đường kẻ */
}

/* 5. Tiêu đề các mục nhỏ (Hình ảnh, Vị trí...) */
#detail-content h5.border-bottom {
    font-size: 1.1rem; /* 17.6px */
    text-transform: uppercase; /* VIẾT HOA */
    font-weight: 700;
    color: #007bff; /* Đổi màu xanh cho nhất quán */
    letter-spacing: 0.5px;
    margin-top: 1.25rem;
}
#detailModal .modal-header.bg-dark {
    background-color: #00A78E !important; 
    color: #ffffff !important;
}

#detailModal .modal-header .modal-title {
    color: #ffffff;
}
#detail-content .mt-3 > .mb-3 > h5 {
    font-size: 1.1rem; 
    text-transform: uppercase; 
    font-weight: 700;
    color: #007bff;
    letter-spacing: 0.5px;
    margin-top: 1.25rem;
    padding-bottom: 0.6rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

/* Tùy chỉnh lại kích thước bản đồ cho full-width */
#detail-map {
    height: 350px !important; /* Tăng chiều cao lên 350px */
    border: 1px solid #dee2e6 !important; /* Border mảnh hơn */
    border-radius: 8px !important; /* Bo góc đẹp */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); /* Thêm bóng đổ nhẹ */
}
/* --- CSS MỚI THÊM CHO CHATBOT --- */
#chatbot-floating-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}
#chatbot-toggle-btn {
    width: 60px;
    height: 60px;
    font-size: 24px;
    border: none;
    background-color: #00A78E; /* Đổi màu cho hợp trang Admin */
    color: white;
}

.chatbot-container {
    display: none; /* Ẩn ban đầu */
    flex-direction: column;
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    max-height: 80vh;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    z-index: 10000;
    overflow: hidden;
}
.chatbot-header {
    background-color: #00A78E; /* Màu trang Admin */
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chatbot-body {
    flex-grow: 1;
    overflow-y: auto;
    padding: 10px;
    max-height: 400px; /* Giới hạn chiều cao */
    background-color: #f8f9fa;
}
.chatbot-input-form {
    display: flex;
    padding: 10px;
    border-top: 1px solid #dee2e6;
    background-color: #fff;
}
/* Style cho tin nhắn (dùng lại style .comment-item của bạn) */
.chat-message {
    display: flex;
    margin-bottom: 1.25rem;
}
.chat-message .comment-avatar {
    width: 32px; /* Nhỏ hơn 1 chút */
    height: 32px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}
.chat-message .comment-content {
    background-color: #e9ecef;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    flex-grow: 1;
    box-shadow: none;
    max-width: 85%;
}
/* Tin nhắn của người dùng */
.chat-message.user {
    justify-content: flex-end;
}
.chat-message.user .comment-content {
    background-color: #00A78E;
    color: white;
    border-color: #00A78E;
    border-top-right-radius: 0;
}
.chat-message.user .comment-avatar {
    margin-right: 0;
    margin-left: 10px;
    order: 2; /* Đảo avatar qua phải */
}
.chat-message.user .comment-content {
    order: 1;
}
/* Tin nhắn của Bot */
.chat-message.bot .comment-content {
    background-color: #fff;
    border-top-left-radius: 0;
}
.chat-message .comment-user-name {
    font-weight: 700;
}
.chat-message .comment-text {
    margin-top: 0.25rem;
    line-height: 1.5;
    word-wrap: break-word;
}
.chat-message.typing {
    color: #6c757d;
    font-style: italic;
}

    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm top-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-user-shield"></i> Bảng Điều Khiển
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                 <a href="#" class="nav-link dropdown-toggle d-flex align-items-center text-white" data-bs-toggle="dropdown">
                    <img src="<?php echo htmlspecialchars($_SESSION['user_avatar_url'] ?? 'https://ui-avatars.com/api/?name=A'); ?>" alt="Avatar" class="nav-avatar-img me-2">
                    <?php echo htmlspecialchars($_SESSION['user_fullname']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../index.php" target="_blank">Về trang chủ</a></li>
                    <li><a class="dropdown-item" href="../profile.php" target="_blank">Trang cá nhân</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Đăng xuất</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="sidebar"> 
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php if($current_page == 'index.php') echo 'active'; ?>">
                <i class="fas fa-tachometer-alt"></i> Quản lý Báo cáo
            </a>
        </li>
        <li class="nav-item">
           <a href="users.php" class="nav-link <?php if($current_page == 'users.php') echo 'active'; ?>">
                <i class="fas fa-users"></i> Quản lý Người dùng
            </a>
        </li>
        <li class="nav-item">
            <a href="stats.php" class="nav-link <?php if($current_page == 'stats.php') echo 'active'; ?>">
                <i class="fas fa-list"></i> Thống Kê Sự cố
            </a>
        
    </ul>
    
    <div class="sidebar-footer">
        <hr class="sidebar-divider">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle sidebar-user-toggle" data-bs-toggle="dropdown">
                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar_url'] ?? 'https://ui-avatars.com/api/?name=A'); ?>" alt="Avatar" class="nav-avatar-img me-2">
                <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['user_fullname'])[0]); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="../index.php">Về trang chủ</a></li>
                <li><a class="dropdown-item" href="../profile.php">Trang cá nhân</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php">Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</div>

<main class="main-content">