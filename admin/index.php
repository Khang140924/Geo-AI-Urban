<?php 
// 1. Bao gồm header
include 'header_admin.php'; 

// --- MỚI: LẤY DANH SÁCH ĐƠN VỊ ĐỂ PHÂN CÔNG ---
try {
    $stmt_units = $pdo->query("SELECT * FROM assigned_units ORDER BY name");
    $units = $stmt_units->fetchAll();
} catch (Exception $e) { $units = []; }
?>

<div class="page-card">

    <h1 class="h2">Quản lý Báo cáo</h1>
    <p>Nơi xử lý và lọc các báo cáo từ người dân.</p>

    <div class="accordion mb-4" id="accordionFilter">
        <div class="accordion-item shadow-sm border-0">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter">
                    <i class="fas fa-filter me-2"></i> Tùy chọn Lọc & Tìm kiếm
                </button>
            </h2>
            <div id="collapseFilter" class="accordion-collapse collapse" data-bs-parent="#accordionFilter">
                <div class="accordion-body">
                    <form id="filterForm" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Lọc theo Danh mục</label>
                            <select id="filterCategory" class="form-select">
                                <option value="all" selected>Tất cả Danh mục</option>
                                <?php
                                    try {
                                        $stmt = $pdo->query("SELECT MIN(id) as id, name FROM categories GROUP BY name ORDER BY name");
                                        while ($row = $stmt->fetch()) {
                                            echo "<option value='{$row['id']}'>".htmlspecialchars($row['name'])."</option>";
                                        }
                                    } catch (Exception $e) {}
                                ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Lọc theo Thời gian</label>
                            <select id="filterTime" class="form-select">
                                <option value="all" selected>Toàn bộ thời gian</option>
                                <option value="7">7 ngày qua</option>
                                <option value="30">30 ngày qua</option>
                                <option value="today">Hôm nay</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Lọc</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table main-table table-hover align-middle">
            <thead> 
                <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Danh mục</th>
                    <th>Người gửi</th>
                    <th>Mô tả</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Hành động</th> 
                </tr>
            </thead>
            <tbody id="report-table-body">
                <tr>
                    <td colspan="8" class="text-center p-5">
                        <span class="spinner-border spinner-border-sm"></span> Đang tải báo cáo...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav id="pagination-container" class="d-flex justify-content-center mt-4" aria-label="Page navigation"></nav>

</div> 

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-tasks"></i> Phân công xử lý</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignForm">
                    <input type="hidden" name="report_id" id="assign_report_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn đơn vị xử lý:</label>
                        <select name="unit_id" class="form-select" required>
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ghi chú công việc:</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Ví dụ: Cây sắp đổ, cần xử lý gấp..."></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-info text-white">Lưu phân công</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true" style="--bs-modal-width: 800px;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Chi tiết Báo cáo #<span id="detail-report-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4" id="detailModalBody">
                <div id="detail-loading" class="text-center p-5">
                    <span class="spinner-border" style="width: 3rem; height: 3rem;"></span>
                    <p class="mt-3">Đang tải chi tiết...</p>
                </div>

                <div id="detail-content" class="d-none">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <h4 class="mb-3" id="detail-category"></h4>
                                <p class="text-muted"><strong class="text-dark">Người gửi:</strong> <span id="detail-user"></span></p>
                                <p class="text-muted"><strong class="text-dark">Ngày gửi:</strong> <span id="detail-date"></span></p>
                                <p class="text-muted"><strong class="text-dark">Trạng thái:</strong> <span id="detail-status"></span></p>
                                <p class="text-dark"><strong>Mô tả:</strong><br><span id="detail-desc" class="fs-6"></span></p>
                            </div>
                            <div class="mb-3">
                                <h5 class="border-bottom pb-2">Hình ảnh</h5>
                                <a id="detail-image-link" href="#" target="_blank">
                                    <img id="detail-image" src="" class="img-fluid rounded border" alt="Hình ảnh báo cáo">
                                </a>
                            </div>
                            
                        </div>
                        <div class="col-md-5">
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">Lịch sử Phân công</h5>
                                <div id="detail-history-list" class="report-timeline"></div>
                            </div>
                            <div>
                                <h5 class="border-bottom pb-2 mb-3">Bình luận</h5>
                                <div id="detail-comment-list" class="report-comments" style="max-height: 400px; overflow-y: auto; padding-right: 10px;"></div>
                            </div>
                            <div class="mt-4"> <h5 class="border-bottom pb-2 mb-3">Vị trí Sự cố</h5>
                                <div id="report-map" style="height: 300px; width: 100%; border-radius: 5px; border: 1px solid #ddd;">

                                </div>
                            </div>
                        
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    let detailMap = null;
// --- HÀM CHÍNH: Tải báo cáo ---
// --- THAY ĐỔI: Thêm tham số 'page' ---
function loadReports(categoryId, timeRange, page) {
    let tableBody = $('#report-table-body');
    tableBody.html('<tr><td colspan="8" class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Đang tải...</td></tr>');
    
    // Xóa phân trang cũ
    $('#pagination-container').empty(); 

    $.ajax({
        url: 'api/get_filtered_reports.php',
        type: 'GET',
        // --- THAY ĐỔI: Thêm 'page' vào data ---
        data: { 
            category_id: categoryId, 
            time_range: timeRange,
            page: page
        },
        dataType: 'json',
        // --- THAY ĐỔI: 'reports' giờ là 'response' (một object) ---
        success: function(response) {
            tableBody.empty(); 

            let reports = response.reports; // Lấy mảng reports từ response

            if (reports.length === 0) {
                tableBody.html('<tr><td colspan="8" class="text-center p-5">Không tìm thấy báo cáo nào.</td></tr>');
                return;
            }

            reports.forEach(function(report) {
                // Logic trạng thái (Giữ nguyên)
                let statusHtml = '';
                switch (report.status) {
                    case 'Mới': statusHtml = '<span class="badge rounded-pill badge-status status-moi"><i class="fas fa-lightbulb"></i> Mới</span>'; break;
                    case 'Đang xử lý': statusHtml = '<span class="badge rounded-pill badge-status status-dang-xu-ly"><i class="fas fa-spinner fa-spin"></i> Đang xử lý</span>'; break;
                    case 'Đã hoàn thành': statusHtml = '<span class="badge rounded-pill badge-status status-da-hoan-thanh"><i class="fas fa-check-circle"></i> Đã hoàn thành</span>'; break;
                    case 'Chờ duyệt': statusHtml = '<span class="badge rounded-pill badge-status status-cho-duyet"><i class="fas fa-clock"></i> Chờ duyệt</span>'; break;
                    case 'Không hợp lệ': statusHtml = '<span class="badge rounded-pill badge-status status-khong-hop-le"><i class="fas fa-times-circle"></i> Không hợp lệ</span>'; break;
                    default: statusHtml = `<span class="badge rounded-pill bg-light text-dark">${htmlspecialchars(report.status)}</span>`;
                }
                if (report.status === 'Đang xử lý' && report.assigned_unit_name) {
                    statusHtml += `<br><small class_id="assigned-unit-text">${htmlspecialchars(report.assigned_unit_name)}</small>`;
                }
                
                // Logic Disabled (Giữ nguyên)
                const status = report.status;
                let disableProcessing = (status === 'Chờ duyệt' || status === 'Đang xử lý' || status === 'Đã hoàn thành' || status === 'Không hợp lệ') ? 'disabled' : '';
                let disableCompleted = (status === 'Chờ duyệt' || status === 'Đã hoàn thành' || status === 'Không hợp lệ') ? 'disabled' : '';
                let disableApprove = (status === 'Mới' || status === 'Đang xử lý' || status === 'Đã hoàn thành') ? 'disabled' : '';
                let disablePending = (status === 'Chờ duyệt') ? 'disabled' : '';
                let disableInvalid = (status === 'Không hợp lệ' || status === 'Đã hoàn thành') ? 'disabled' : '';
                
                // HTML (Giữ nguyên)
                let rowHtml = `
                    <tr>
                        <td>${report.id}</td>
                        <td>
                            <a href="../${htmlspecialchars(report.image_url)}" target="_blank">
                                <img src="../${htmlspecialchars(report.image_url)}" width="100" class="img-thumbnail">
                            </a>
                        </td>
                        <td>${htmlspecialchars(report.category_name)}</td>
                        <td>${htmlspecialchars(report.user_name)}</td>
                        <td><small>${htmlspecialchars(report.description)}</small></td>
                        <td>${formatSqlDate(report.created_at)}</td>
                        <td>${statusHtml}</td> <td class="action-buttons text-end">
                            
                            <button class="btn btn-info btn-sm btn-assign text-white me-1" 
                                    data-id="${report.id}" 
                                    data-bs-toggle="modal" data-bs-target="#assignModal"
                                    title="Phân công xử lý">
                                <i class="fas fa-bullhorn"></i>
                            </button>

                            <button class="btn btn-dark btn-sm btn-details me-1" 
                                    data-id="${report.id}"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-update dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item ${disableApprove}" href="#" onclick="updateStatus(${report.id}, 'Mới')"><b>Duyệt (Mới)</b></a></li>
                                    <li><a class="dropdown-item ${disableProcessing}" href="#" onclick="updateStatus(${report.id}, 'Đang xử lý')">Đang xử lý</a></li>
                                    <li><a class="dropdown-item ${disableCompleted}" href="#" onclick="updateStatus(${report.id}, 'Đã hoàn thành')">Đã hoàn thành</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item ${disablePending}" href="#" onclick="updateStatus(${report.id}, 'Chờ duyệt')">Quay lại (Chờ duyệt)</a></li>
                                    <li><a class="dropdown-item ${disableInvalid}" href="#" onclick="updateStatus(${report.id}, 'Không hợp lệ')">Từ chối (Không hợp lệ)</a></li>
                                 </ul>
                            </div>
                            <button class="btn btn-delete" data-id="${report.id}" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.append(rowHtml);
            });

            // --- MỚI: Vẽ các nút phân trang ---
            drawPagination(response.total_pages, response.current_page);

        },
        error: function() { tableBody.html('<tr><td colspan="8" class="text-danger text-center">Lỗi tải báo cáo.</td></tr>'); }
    });
}

// --- MỚI: HÀM VẼ PHÂN TRANG ---
function drawPagination(totalPages, currentPage) {
    let paginationContainer = $('#pagination-container');
    paginationContainer.empty();
    if (totalPages <= 1) return; // Không cần nếu chỉ có 1 trang

    let paginationHtml = '<ul class="pagination">';

    // Nút "Previous"
    paginationHtml += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
        </li>
    `;

    // Logic hiển thị các nút số (tối đa 5 nút)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    // Điều chỉnh nếu gần đầu/cuối
    if (currentPage - 2 < 1) { endPage = Math.min(totalPages, 5); }
    if (currentPage + 2 > totalPages) { startPage = Math.max(1, totalPages - 4); }

    if (startPage > 1) {
        paginationHtml += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
        if (startPage > 2) {
             paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
             paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Nút "Next"
    paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
        </li>
    `;

    paginationHtml += '</ul>';
    paginationContainer.html(paginationHtml);
}


// --- CẬP NHẬT TRẠNG THÁI ---
function updateStatus(reportId, newStatus) {
    if ($(event.target).hasClass('disabled')) return;
    
    Swal.fire({
        title: 'Xác nhận cập nhật?',
        text: `Đổi báo cáo #${reportId} thành "${newStatus}"?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'api/update_status.php',
                type: 'POST',
                data: { report_id: reportId, status: newStatus },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Thành công!',
                            text: 'Đã cập nhật trạng thái báo cáo.',
                            icon: 'success',
                            timer: 1500, // Tự động đóng sau 1.5s
                            showConfirmButton: false
                        });
                        // --- THAY ĐỔI: Tải lại trang hiện tại
                        // Lấy số trang đang active
                        let currentPage = parseInt($('#pagination-container .page-item.active .page-link').data('page')) || 1;
                        loadReports($('#filterCategory').val(), $('#filterTime').val(), currentPage);
                    } else {
                        Swal.fire('Lỗi!', response.message, 'error');
                    }
                },
                error: function() { Swal.fire('Lỗi!', 'Không thể cập nhật.', 'error'); }
            });
        }
    });
}

// --- TIỆN ÍCH ---
function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function formatSqlDate(sqlDate) {
    let dateInput = (typeof sqlDate === 'object' && sqlDate !== null && sqlDate.hasOwnProperty('date')) ? sqlDate.date : sqlDate;
    try {
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return "Invalid Date";
        return date.toLocaleString('vi-VN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
    } catch(e) { return "Error"; }
}


// Biến global để giữ map

// Hàm chính để đổ dữ liệu vào modal
function populateDetailModal(data) {
    if (!data.details) {
        $('#detailModalBody').html('<p class="text-danger">Lỗi: Không tìm thấy dữ liệu.</p>');
        return;
    }

    var details = data.details;
    var history = data.history;
    var comments = data.comments;

    // 1. Thông tin cơ bản
    $('#detail-report-id').text(details.id);
    $('#detail-category').text(details.category_name);
    // SỬA: Xử lý user bị NULL
    $('#detail-user').text(details.user_name || 'Người dùng đã xóa'); 
    $('#detail-date').text(formatSqlDate(details.created_at));
    $('#detail-desc').html(htmlspecialchars(details.description).replace(/\n/g, '<br>'));
    
    // Status (tái sử dụng logic cũ)
    let statusHtml = '';
    switch (details.status) {
        case 'Mới': statusHtml = '<span class="badge rounded-pill badge-status status-moi">Mới</span>'; break;
        case 'Đang xử lý': statusHtml = '<span class="badge rounded-pill badge-status status-dang-xu-ly">Đang xử lý</span>'; break;
        case 'Đã hoàn thành': statusHtml = '<span class="badge rounded-pill badge-status status-da-hoan-thanh">Đã hoàn thành</span>'; break;
        case 'Chờ duyệt': statusHtml = '<span class="badge rounded-pill badge-status status-cho-duyet">Chờ duyệt</span>'; break;
        case 'Không hợp lệ': statusHtml = '<span class="badge rounded-pill badge-status status-khong-hop-le">Không hợp lệ</span>'; break;
        default: statusHtml = `<span class="badge rounded-pill bg-light text-dark">${htmlspecialchars(details.status)}</span>`;
    }
    // MỚI: Thêm đơn vị nếu có (lấy từ details thay vì data.details)
    if (details.status === 'Đang xử lý' && details.assigned_unit_name) {
         statusHtml += `<br><small class="text-muted fst-italic">ĐV: ${htmlspecialchars(details.assigned_unit_name)}</small>`;
    }
    $('#detail-status').html(statusHtml);

    // 2. Hình ảnh
    $('#detail-image-link').attr('href', '../' + htmlspecialchars(details.image_url));
    $('#detail-image').attr('src', '../' + htmlspecialchars(details.image_url));

    

    // 4. Lịch sử phân công
    var historyList = $('#detail-history-list');
    historyList.empty();
    if (history.length > 0) {
        history.forEach(function(item) {
            let noteHtml = (item.note && item.note.trim() !== '') ? `<div class="timeline-note">${htmlspecialchars(item.note)}</div>` : '';
            // SỬA: Xử lý admin_name bị NULL
            let adminName = item.admin_name ? htmlspecialchars(item.admin_name) : 'Hệ thống';
            
            let itemHtml = `
                <div class="timeline-item">
                    <div class="timeline-icon"><i class="fas fa-tasks"></i></div>
                    <div class="timeline-content">
                        Giao cho <strong>${htmlspecialchars(item.unit_name)}</strong>
                        <span class="timeline-user">(Bởi: ${adminName})</span>
                        ${noteHtml}
                        <div class="timeline-time">${formatSqlDate(item.assigned_at)}</div>
                    </div>
                </div>
            `;
            historyList.append(itemHtml);
        });
    } else {
        historyList.html('<p class="text-muted fst-italic">Chưa có lịch sử phân công.</p>');
    }

    // 5. Bình luận
    var commentList = $('#detail-comment-list');
    commentList.empty();
    if (comments.length > 0) {
        comments.forEach(function(comment) {
            let commentClass = 'comment-user';
            let avatar = comment.avatar_url ? `../${htmlspecialchars(comment.avatar_url)}` : '../assets/images/anhdaidien.jpg'; 
            // SỬA: Xử lý user_name bị NULL
            let userName = comment.user_name ? htmlspecialchars(comment.user_name) : 'Người dùng đã xóa';

            let itemHtml = `
                <div class="comment-item ${commentClass}">
                    <img src="${avatar}" class="comment-avatar" alt="avatar" onerror="this.src='../assets/images/anhdaidien.jpg'">
                    <div class="comment-content">
                        <strong class="comment-user-name">${userName}</strong>
                        <div class="comment-text">${htmlspecialchars(comment.comment_text)}</div>
                        <div class="comment-time">${formatSqlDate(comment.comment_at)}</div>
                    </div>
                </div>
            `;
            commentList.append(itemHtml);
        });
    } else {
        commentList.html('<p class="text-muted fst-italic">Chưa có bình luận nào.</p>');
    }
}
// --- CHẠY KHI LOAD TRANG ---
$(document).ready(function() {
    // --- THAY ĐỔI: Tải trang 1
    loadReports('all', 'all', 1);

    $('#filterForm').on('submit', function(event) {
        event.preventDefault(); 
        // --- THAY ĐỔI: Khi lọc, luôn quay về trang 1
        loadReports($('#filterCategory').val(), $('#filterTime').val(), 1);
    });

    // --- MỚI: XỬ LÝ CLICK PHÂN TRANG ---
    $('#pagination-container').on('click', 'a.page-link', function(e) {
        e.preventDefault();
        let pageItem = $(this).closest('.page-item');
        if (pageItem.hasClass('disabled') || pageItem.hasClass('active')) {
            return; // Không làm gì nếu click vào nút disabled hoặc trang hiện tại
        }

        let page = $(this).data('page');
        let categoryId = $('#filterCategory').val();
        let timeRange = $('#filterTime').val();
        
        loadReports(categoryId, timeRange, page);
    });

    // XÓA BÁO CÁO
    $('#report-table-body').on('click', '.btn-delete', function() {
        let reportId = $(this).data('id');
        Swal.fire({
            title: 'Xóa vĩnh viễn?',
            text: `Báo cáo #${reportId} sẽ bị xóa không thể khôi phục!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Xóa ngay!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/delete_report.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ report_id: reportId }),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Đã xóa!',
                                text: 'Báo cáo đã được xóa vĩnh viễn.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            // --- THAY ĐỔI: Tải lại trang hiện tại (hoặc trang 1 nếu hết)
                             let currentPage = parseInt($('#pagination-container .page-item.active .page-link').data('page')) || 1;
                             loadReports($('#filterCategory').val(), $('#filterTime').val(), currentPage);
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    },
                    error: function() { Swal.fire('Lỗi!', 'Không thể xóa.', 'error'); }
                });
            }
        });
    });
    $('#report-table-body').on('click', '.btn-details', function() {
        
        let reportId = $(this).data('id');
        let modal = $('#detailModal');
        let modalBody = $('#detailModalBody');
        let loadingDiv = $('#detail-loading');
        let contentDiv = $('#detail-content');

        // Reset modal về trạng thái loading
        contentDiv.addClass('d-none');
        loadingDiv.removeClass('d-none');
        
        // Load thư viện map (nếu cần)
        // Sẽ load khi hàm populateDetailModal chạy
        
        // Mở modal
        modal.modal('show');

        // Gọi API
        $.ajax({
            url: 'api/get_report_details.php',
            type: 'GET',
            data: { id: reportId },
            dataType: 'json',
            success: function(response) {
                // Lưu data vào modal để bước sau dùng
                modal.data('mapResponse', response); 
                
                // Vẽ mọi thứ TRỪ cái map
                populateDetailModal(response); 

                // Hiển thị nội dung, ẩn loading
                contentDiv.removeClass('d-none');
                loadingDiv.addClass('d-none');
            },
            error: function(jqXHR) {
                let errorMsg = 'Không thể tải chi tiết báo cáo.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                }
                // Hiển thị lỗi bên trong modal body
                $('#detailModalBody').html(`<div class="p-5 text-center text-danger"><h3>Lỗi!</h3><p>${errorMsg}</p></div>`);
            }
        });
    });
    

    
    // --- XỬ LÝ PHÂN CÔNG (ĐÃ SỬA LỖI) ---
// 1. Đẩy ID vào Modal
$('#report-table-body').on('click', '.btn-assign', function() {
    let id = $(this).data('id');
    $('#assign_report_id').val(id);
});

// 2. Gửi Ajax Phân công
$('#assignForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'api/assign_task.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Đóng modal
                $('#assignModal').modal('hide'); 

                // Hiển thị thông báo thành công
                Swal.fire({
                    title: 'Đã phân công!',
                    text: 'Đã giao nhiệm vụ cho đơn vị xử lý.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Tải lại bảng
                let currentPage = parseInt($('#pagination-container .page-item.active .page-link').data('page')) || 1;
                loadReports($('#filterCategory').val(), $('#filterTime').val(), currentPage);
            } else {
                // Báo lỗi nếu API trả về { success: false }
                Swal.fire('Lỗi!', response.message, 'error');
            }
        },
        error: function() {
            // Báo lỗi nếu không gọi được API
            Swal.fire('Lỗi!', 'Không thể kết nối server.', 'error');
        }
     }); 
    });
    $('#detailModal').on('shown.bs.modal', function() {
        // Lấy dữ liệu mà chúng ta đã lưu trong sự kiện click .btn-details
        let data = $(this).data('mapResponse');

        if (data && data.details) {
            let lat = data.details.latitude;
            let lon = data.details.longitude;

            // Kiểm tra xem tọa độ có hợp lệ không
            if (lat && lon) {

                // Xóa map cũ đi nếu có (để tránh lỗi khi mở lại)
                if (detailMap) {
                    detailMap.remove();
                }

                // Khởi tạo map
                detailMap = L.map('report-map').setView([lat, lon], 16); // 16 là mức zoom

                // Thêm lớp bản đồ nền (dùng OpenStreetMap miễn phí)
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(detailMap);

                // Thêm marker (cái ghim) vào đúng vị trí
                L.marker([lat, lon]).addTo(detailMap)
                    .bindPopup(htmlspecialchars(data.details.category_name)) // Thêm nhãn
                    .openPopup(); // Mở nhãn

                // Fix lỗi thỉnh thoảng map không load hết
                 setTimeout(function() {
                    detailMap.invalidateSize();
                }, 100);

            } else {
                 // Ẩn map nếu không có tọa độ
                $('#report-map').html('<p class="text-muted p-3">Báo cáo này không có dữ liệu vị trí.</p>');
            }
        }
    });
    /* ===================================== */


});
</script>

<?php include 'footer_admin.php'; ?>