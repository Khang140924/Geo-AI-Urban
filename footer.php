<?php
// --- PHP TẢI DANH MỤC (CHẠY 1 LẦN) ---
$category_options_html = "";
if (isset($pdo)) {
    try {
        // Dùng cú pháp SQL chuẩn
        $stmt_categories_footer = $pdo->query("SELECT MIN(id) as id, name FROM categories GROUP BY name ORDER BY name");
        $category_options_html .= "<option value='' disabled selected>-- Vui lòng chọn --</option>";
        while ($row_category_footer = $stmt_categories_footer->fetch()) {
            $category_options_html .= "<option value='" . $row_category_footer['id'] . "'>" . htmlspecialchars($row_category_footer['name']) . "</option>";
        }
    } catch (Exception $e) {
        error_log("Footer Category Load Error: " . $e->getMessage());
        $category_options_html .= "<option value='' disabled>Lỗi tải danh mục</option>";
    }
} else {
    $category_options_html .= "<option value='' disabled>Lỗi kết nối CSDL</option>";
}

// Lấy avatar người dùng hiện tại từ session (do header.php cung cấp)
$current_user_avatar = $_SESSION['user_avatar_url'] ?? "https://ui-avatars.com/api/?name=User&background=00A78E&color=fff&size=32";
?>

<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Gửi Báo Cáo Sự Cố Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="report-alert" class="alert d-none" role="alert"></div>
                <form id="reportForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-5 image-upload-col">
                            <label for="report_image_input" class="form-label fw-bold mb-2">📸 Tải ảnh sự cố (bắt buộc)</label>
                            <div id="image_preview_box" class="image-preview-container" onclick="document.getElementById('report_image_input').click();">
                                <img id="image_preview" src="#" alt="Xem trước ảnh" style="display: none;" />
                                <div class="image-preview-placeholder">
                                    <i class="fas fa-camera"></i>
                                    Nhấn để chọn hoặc kéo thả ảnh vào đây
                                </div>
                            </div>
                            <input type="file" class="form-control" name="report_image" id="report_image_input" accept="image/*" required style="display: none;">
                            <small class="form-text text-muted d-block mt-1">Định dạng hỗ trợ: JPG, PNG, GIF.</small>
                        </div>
                        <div class="col-md-7 report-info-col">
                            <div class="mb-3">
                                <label for="category_id" class="form-label fw-bold">📂 Chọn danh mục</label>
                                <select class="form-select" name="category_id" id="category_id" required>
                                    <?php echo $category_options_html; // Sử dụng biến đã tạo ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">📝 Nội dung mô tả</label>
                                <textarea class="form-control" name="description" id="description" rows="4" required placeholder="Mô tả chi tiết về sự cố bạn gặp phải..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">📍 Vị trí sự cố</label>
                                <div id="map-picker-leaflet" style="height: 200px; border-radius: 8px; border: 1px solid #ddd; z-index: 1;">
                                    <p class="text-center p-4 text-muted small">Đang tải bản đồ...</p>
                                </div>
                                <small class="form-text text-muted">Tự động lấy vị trí hoặc cho phép ghim trên bản đồ.</small>
                                <input type="hidden" name="latitude" id="latitude" value="10.7769">
                                <input type="hidden" name="longitude" id="longitude" value="106.7009">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btn-submit-report">Gửi Báo Cáo</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Xác Nhận Xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                <p>Bạn có chắc chắn muốn <strong>xóa vĩnh viễn</strong> báo cáo này không?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete">Xóa Báo Cáo</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh Sửa Báo Cáo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edit-report-alert" class="alert d-none"></div>
                <form id="editReportForm">
                    <input type="hidden" name="edit_report_id" id="edit_report_id">
                    <div class="mb-3">
                        <label class="form-label">📂 Chọn danh mục</label>
                        <select class="form-select" name="edit_category_id" id="edit_category_id" required>
                            <?php echo $category_options_html; // Sử dụng lại biến đã tạo ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📝 Nội dung mô tả</label>
                        <textarea class="form-control" name="edit_description" id="edit_description" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btn-save-edit">Lưu Thay Đổi</button>
            </div>
        </div>
    </div>
</div>

<div class="chatbot-container" id="chatbot-container">
    <div class="chatbot-header">
        <strong><i class="fas fa-robot"></i> Chatbot Hỗ trợ</strong>
        <button type="button" class="btn-close btn-close-white" id="chatbot-close-btn"></button>
    </div>

    <div class="chatbot-body" id="chatbot-body">
        <div class="chat-message bot">
            <div class="comment-avatar"><i class="fas fa-robot"></i></div>
            <div class="comment-content">
                <span class="comment-user-name">Bot Hỗ trợ</span>
                <div class="comment-text">Chào bạn, tôi có thể giúp gì cho bạn? (VD: "kiểm tra trạng thái", "cách gửi báo cáo")</div>
            </div>
        </div>
    </div>

    <div class="chatbot-input-form">
        <div class="image-preview-area" id="chatbot-image-preview-area" style="display:none;">
            <img id="chatbot-selected-image" src="#" alt="Ảnh đã chọn" class="img-thumbnail mb-2" style="max-width: 100px; max-height: 100px; object-fit: cover;">
            <button type="button" class="btn btn-sm btn-danger remove-image-btn" id="chatbot-remove-image-btn"><i class="fas fa-times"></i></button>
        </div>

        <input type="file" id="chatbot-image-input" accept="image/*" style="display: none;">
        <button type="button" class="btn btn-secondary me-2" id="chatbot-upload-image-btn" title="Gửi ảnh">
            <i class="fas fa-image"></i>
        </button>
        <input type="text" class="form-control" id="chatbot-input" placeholder="Nhập câu hỏi hoặc nội dung ảnh...">
        <button class="btn btn-primary btn-send-comment" id="chatbot-send-btn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet-geosearch@3/dist/geosearch.umd.js"></script>

<script>
// === BẮT ĐẦU THÊM MỚI (LEAFLET) ===
let leafletMap, leafletMarker;
const defaultCoordsLeaflet = {
    lat: 10.7769,
    lng: 106.7009
}; // Mặc định ở TPHCM
// === KẾT THÚC THÊM MỚI ===
const currentUserAvatarUrl = '<?php echo htmlspecialchars($current_user_avatar); ?>';

function encodeURIComponentSafe(str) {
    try {
        if (typeof str !== 'string') str = String(str);
        return encodeURIComponent(str);
    } catch (e) {
        return 'User';
    }
}

// ----- Shared Utility Functions -----
function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    };
    return str.replace(/[&<>"']/g, m => map[m]);
}

function htmlspecialchars_decode(str) {
    if (typeof str !== 'string') return '';
    const map = {
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&quot;': '"',
        '&#39;': "'"
    };
    return str.replace(/(&amp;|&lt;|&gt;|&quot;|&#39;)/g, m => map[m]);
}

function formatSqlDate(sqlDateObject) {
    // (Hàm cũ này vẫn giữ lại, phòng khi bạn cần dùng ở chỗ khác)
    try {
        if (sqlDateObject && sqlDateObject.date) {
            const dateString = sqlDateObject.date.substring(0, 19);
            const isoString = dateString.replace(' ', 'T');
            const date = new Date(isoString);

            if (isNaN(date.getTime())) {
                const fallbackDate = new Date(sqlDateObject.date);
                if (isNaN(fallbackDate.getTime())) return 'Ngày không hợp lệ';
                return fallbackDate.toLocaleString('vi-VN', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            return date.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            return 'Không rõ thời gian';
        }
    } catch (e) {
        console.error("Lỗi formatSqlDate:", e, sqlDateObject);
        return 'Lỗi ngày tháng';
    }
}

// === HÀM TÍNH THỜI GIAN (ĐÃ SỬA THEO YÊU CẦU "SAU 2 NGÀY") ===
function formatTimeAgo(dateInput) {
    let dateString;

    try {
        // Kịch bản 1: Input là object { date: "..." }
        if (typeof dateInput === 'object' && dateInput !== null && dateInput.date) {
            dateString = dateInput.date;
        }
        // Kịch bản 2: Input là string "..."
        else if (typeof dateInput === 'string') {
            dateString = dateInput;
        }
        // Kịch bản 3: Không có dữ liệu
        else {
            return 'Không rõ thời gian';
        }

        // 1. Lấy chuỗi ngày tháng
        const cleanDateString = dateString.substring(0, 19).replace(' ', 'T');

        // 2. Tạo đối tượng Date
        let date = new Date(cleanDateString);

        // 3. Kiểm tra nếu ngày không hợp lệ (fallback)
        if (isNaN(date.getTime())) {
            date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return 'Ngày không hợp lệ';
            }
        }

        // 4. Tính toán chênh lệch
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 0) return 'Vừa xong';

        // Dưới 1 phút
        if (seconds < 60) {
            return 'Vừa xong';
        }

        // Dưới 1 giờ
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return `${minutes} phút trước`;
        }

        // Dưới 1 ngày
        const hours = Math.floor(minutes / 60);
        if (hours < 24) {
            return `${hours} giờ trước`;
        }

        // === THAY ĐỔI LOGIC TẠI ĐÂY ===
        const days = Math.floor(hours / 24);

        // Chỉ hiển thị "Hôm qua" nếu là 1 ngày
        if (days < 2) {
            return 'Hôm qua';
        }

        // Từ 2 ngày trở lên, hiển thị ngày/tháng/năm
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
        const year = date.getFullYear();
        return `Ngày ${day} tháng ${month} năm ${year}`;
        // === KẾT THÚC THAY ĐỔI ===

    } catch (e) {
        console.error("Lỗi formatTimeAgo:", e, dateInput);
        return 'Lỗi ngày tháng';
    }
}


// ----- JavaScript Specific to index.php (Feed) -----
function loadNewsfeed(categoryId = 'all') {
    let container = $('#newsfeed-container');
    if (container.length === 0) return;
    let skeletonHtml = `
    <div class="skeleton-card"><div class="skeleton-header"><div class="skeleton-avatar"></div><div class="skeleton-user"><div class="skeleton-line w-50"></div><div class="skeleton-line w-75"></div></div></div><div class="skeleton-content"></div></div>
    <div class="skeleton-card"><div class="skeleton-header"><div class="skeleton-avatar"></div><div class="skeleton-user"><div class="skeleton-line w-50"></div><div class="skeleton-line w-75"></div></div></div><div class="skeleton-content"></div></div>`;
    container.html(skeletonHtml);
    $.ajax({
        url: 'api/get_reports.php',
        type: 'GET',
        data: {
            category_id: categoryId
        },
        dataType: 'json',
        success: function(reports) {
            container.empty();
            if (reports.length === 0) {
                container.html('<div class="card report-card p-4 text-center text-muted">Không có báo cáo nào cho mục này.</div>');
                return;
            }
            reports.forEach(function(report) {

    let statusClass = 'badge-status-new';
    if (report.status === 'Đang xử lý') statusClass = 'badge-status-processing';
    if (report.status === 'Đã hoàn thành') statusClass = 'badge-status-completed';
    let likedClass = report.user_has_liked == 1 ? 'liked' : '';

    const authorName = report.author_fullname || 'Người dùng';
    let avatarUrl = report.author_avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponentSafe(authorName)}&background=00A78E&color=fff&size=40`;

    // --- THAY THẾ ĐOẠN NÀY TRONG HÀM loadMyReports ---

// 1. Tạo HTML cho tên đơn vị
let unitBadge = '';
if (report.unit_name) {
    unitBadge = `
        <span class="ms-2 d-none d-sm-inline-block" style="color: #ccc;">|</span>
        <span class="ms-2" style="font-size: 0.85rem; color: #0097A7; font-weight: 600;">
            <i class="fas fa-hard-hat text-warning"></i> ${htmlspecialchars(report.unit_name)}
        </span>
    `;
}

// 2. Tạo thẻ bài viết
let cardHtml = `
<div class="card report-card mb-3" id="report-card-${report.id}">
    <div class="card-user-info">
        <div class="user-avatar">
            <img src="${htmlspecialchars(avatarUrl)}" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
        </div>
        <div class="user-name-time">
            <span class="user-name">${htmlspecialchars(authorName)}</span>
            <span class="post-time">
                <i class="fas fa-clock"></i> ${formatTimeAgo(report.created_at)}
                <span class="badge-status ${statusClass} ms-2">${htmlspecialchars(report.status)}</span>
                <span class="badge-category ms-2">${htmlspecialchars(report.category_name)}</span>
                
                ${unitBadge}
                </span>
        </div>
        
        <div class="dropdown ms-auto">
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="showEditModal(${report.id})"><i class="fas fa-edit me-2"></i> Chỉnh sửa</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteReport(${report.id})"><i class="fas fa-trash me-2"></i> Xóa báo cáo</a></li>
            </ul>
        </div>
    </div>

    <div class="card-content">
        <p class="report-description">${htmlspecialchars(report.description)}</p>
        <img src="${htmlspecialchars(report.image_url)}" class="report-image" alt="Ảnh sự cố">
    </div>
    
    <div class="report-stats"><i class="fas fa-thumbs-up"></i><span id="like-count-${report.id}">${report.like_count ?? 0}</span></div>
    <div class="report-actions">
        <button class="action-button ${likedClass}" id="like-btn-${report.id}" onclick="toggleLike(${report.id})"><i class="fas fa-thumbs-up"></i> Thích</button>
        <button class="action-button" onclick="showComment(${report.id})"><i class="fas fa-comment"></i> Bình luận</button>
    </div>
    <div class="comment-section" id="comment-section-${report.id}">
        <div class="comment-list" id="comment-list-${report.id}"></div>
    </div>
    <div class="comment-section-input"> 
        <div class="comment-input-form">
            <div class="current-user-avatar">
                <img src="${htmlspecialchars(currentUserAvatarUrl)}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            </div> 
            <input type="text" class="form-control" id="comment-input-${report.id}" placeholder="Viết bình luận..." onkeypress="handleCommentEnter(event, ${report.id})">
            <button class="btn btn-primary btn-send-comment" onclick="postComment(${report.id})"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>`;

container.append(cardHtml);
});
        },
        error: function() {
            container.html('<div class="card report-card p-4 text-center text-danger">Lỗi khi tải dữ liệu báo cáo.</div>');
        }
    });
}

function toggleLike(reportId) {
    let likeButton = $(`#like-btn-${reportId}`);
    let likeCountSpan = $(`#like-count-${reportId}`);
    $.ajax({
        url: 'api/toggle_like.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            report_id: reportId
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                likeCountSpan.text(response.newLikeCount);
                if (response.userHasLiked) {
                    likeButton.addClass('liked');
                } else {
                    likeButton.removeClass('liked');
                }
            } else {
                alert('Lỗi: ' + response.message);
            }
        },
        error: function(jqXHR) {
            alert('Lỗi máy chủ: ' + (jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Vui lòng thử lại.'));
        }
    });
}

function showComment(reportId) {
    let commentSection = $(`#comment-section-${reportId}`);
    let commentList = $(`#comment-list-${reportId}`);

    commentSection.slideToggle(200);

    if (commentSection.is(':visible') && !commentList.data('loaded')) {
        loadComments(reportId);
    }
}

function loadComments(reportId) {
    let commentList = $(`#comment-list-${reportId}`);
    commentList.html('<div class="text-center p-3 text-muted small">Đang tải bình luận...</div>');

    $.ajax({
        url: 'api/get_comments.php',
        type: 'GET',
        data: {
            report_id: reportId
        },
        dataType: 'json',
        success: function(response) {
            commentList.empty();
            if (response.success && response.comments.length > 0) {
                response.comments.forEach(function(comment) {
                    commentList.append(renderComment(comment));
                });
            } else if (response.success) {
                commentList.html('<div class="text-center p-3 text-muted small">Chưa có bình luận nào.</div>');
            } else {
                commentList.html('<div class="text-danger p-3 small">Lỗi tải bình luận.</div>');
            }
            commentList.data('loaded', true);
        },
        error: function() {
            commentList.html('<div class="text-danger p-3 small">Lỗi máy chủ.</div>');
        }
    });
}

function postComment(reportId) {
    let input = $(`#comment-input-${reportId}`);
    let commentText = input.val().trim();
    if (commentText === "") return;

    input.prop('disabled', true);
    $(`#report-card-${reportId}`).find('.comment-section-input .btn-send-comment').prop('disabled', true);


    $.ajax({
        url: 'api/post_comment.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            report_id: reportId,
            comment_text: commentText
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let commentList = $(`#comment-list-${reportId}`);
                if (commentList.find('.small').length > 0) {
                    commentList.empty();
                }
                commentList.prepend(renderComment(response.comment));
                input.val('');
            } else {
                alert("Lỗi: " + response.message);
            }
        },
        error: function(jqXHR) {
            alert('Lỗi máy chủ: ' + (jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Vui lòng thử lại.'));
        },
        complete: function() {
            input.prop('disabled', false);
            $(`#report-card-${reportId}`).find('.comment-section-input .btn-send-comment').prop('disabled', false);
        }
    });
}

function renderComment(comment) {
    const commenterName = comment.user_fullname || 'Người dùng';
    const commentAvatarUrl = comment.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponentSafe(commenterName)}&background=00A78E&color=fff&size=32`;

    return `
        <div class="comment-item">
            <div class="comment-avatar">
                <img src="${htmlspecialchars(commentAvatarUrl)}" alt="Avatar" 
                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
            </div>
            <div class="comment-content">
                <span class="comment-user-name">${htmlspecialchars(commenterName)}</span>
                <div class="comment-text">${htmlspecialchars(comment.comment_text)}</div>
            </div>
        </div>
    `;
}

function handleCommentEnter(event, reportId) {
    if (event.key === 'Enter' || event.keyCode === 13) {
        event.preventDefault();
        postComment(reportId);
    }
}


// ----- JavaScript Specific to profile.php -----
function loadMyReports(sortOrder = 'DESC', labelText = 'Mới nhất') {
    
    // [THÊM MỚI]: Cập nhật chữ trên nút Dropdown cho người dùng biết đang chọn gì
    if ($('#sortDropdownButton').length > 0) {
        $('#sortDropdownButton').text(labelText);
    }

    let container = $('#my-reports-container');
    container.html('<div class="text-center p-5"><span class="spinner-border text-primary"></span><p>Đang tải dữ liệu...</p></div>');

    $.ajax({
        url: 'api/get_my_reports.php',
        type: 'GET',
        // [THAY ĐỔI 2]: Gửi tham số sort lên server
        data: { 
            sort: sortOrder 
        },
        dataType: 'json',
        success: function(response) {

            if (!response.success) {
                container.html(`<div class="alert alert-danger">${htmlspecialchars(response.message || 'Lỗi không xác định từ API.')}</div>`);
                return;
            }

            // Cập nhật thống kê (Giữ nguyên)
            if (response.stats) {
                $('#total-reports').text(response.stats.total);
                $('#processing-reports').text(response.stats.processing ?? 0);
                $('#completed-reports').text(response.stats.completed ?? 0);
            }

            container.empty(); // Xóa loading

            if (response.reports && response.reports.length > 0) {
                response.reports.forEach(function(report) {
                    
                    // --- ĐOẠN NÀY TAO GIỮ NGUYÊN CODE CŨ CỦA MÀY ---
                    let statusClass = 'badge-status-new';
                    if (report.status === 'Đang xử lý') statusClass = 'badge-status-processing';
                    if (report.status === 'Đã hoàn thành') statusClass = 'badge-status-completed';
                    let likedClass = report.user_has_liked == 1 ? 'liked' : '';
                    const authorName = report.author_fullname || 'Người dùng';
                    let avatarUrl = report.author_avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponentSafe(authorName)}&background=00A78E&color=fff&size=40`;

                    // === BƯỚC 1: TẠO BIẾN TÊN ĐƠN VỊ (Giữ nguyên code mày) ===
                    let unitBadge = '';
                    if (report.unit_name) {
                        unitBadge = `
                            <span class="ms-2 d-none d-sm-inline-block" style="color: #ccc;">|</span>
                            <span class="ms-2" style="font-size: 0.85rem; color: #0097A7; font-weight: 600;">
                                <i class="fas fa-hard-hat text-warning"></i> ${htmlspecialchars(report.unit_name)}
                            </span>
                        `;
                    }

                    // === BƯỚC 2: NHÉT BIẾN VÀO HTML (Giữ nguyên code mày) ===
                    let cardHtml = `
                    <div class="card report-card mb-3" id="report-card-${report.id}">
                        <div class="card-user-info">
                            <div class="user-avatar">
                                <img src="${htmlspecialchars(avatarUrl)}" alt="Avatar" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            </div>
                            <div class="user-name-time">
                                <span class="user-name">${htmlspecialchars(authorName)}</span>
                                <span class="post-time">
                                    <i class="fas fa-clock"></i> ${formatTimeAgo(report.created_at)}
                                    <span class="badge-status ${statusClass} ms-2">${htmlspecialchars(report.status)}</span>
                                    <span class="badge-category ms-2">${htmlspecialchars(report.category_name)}</span>
                                    
                                    ${unitBadge}
                                    
                                </span>
                            </div>
                            
                            <div class="dropdown ms-auto">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="showEditModal(${report.id})"><i class="fas fa-edit me-2"></i> Chỉnh sửa</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteReport(${report.id})"><i class="fas fa-trash me-2"></i> Xóa báo cáo</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-content">
                            <p class="report-description">${htmlspecialchars(report.description)}</p>
                            <img src="${htmlspecialchars(report.image_url)}" class="report-image" alt="Ảnh sự cố">
                        </div>
                        
                        <div class="report-stats"><i class="fas fa-thumbs-up"></i><span id="like-count-${report.id}">${report.like_count ?? 0}</span></div>
                        <div class="report-actions">
                            <button class="action-button ${likedClass}" id="like-btn-${report.id}" onclick="toggleLike(${report.id})"><i class="fas fa-thumbs-up"></i> Thích</button>
                            <button class="action-button" onclick="showComment(${report.id})"><i class="fas fa-comment"></i> Bình luận</button>
                        </div>
                        <div class="comment-section" id="comment-section-${report.id}">
                            <div class="comment-list" id="comment-list-${report.id}"></div>
                        </div>
                        <div class="comment-section-input"> 
                            <div class="comment-input-form">
                                <div class="current-user-avatar">
                                    <img src="${htmlspecialchars(currentUserAvatarUrl)}" alt="Avatar" 
                                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                </div> 
                                <input type="text" class="form-control" id="comment-input-${report.id}" placeholder="Viết bình luận..." onkeypress="handleCommentEnter(event, ${report.id})">
                                <button class="btn btn-primary btn-send-comment" onclick="postComment(${report.id})">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>`;

                    container.append(cardHtml);
                });
            } else {
                container.html('<div class="card report-card p-4 text-center text-muted">Bạn chưa gửi báo cáo nào.</div>');
            }
        },
        error: function(jqXHR) {
            let errorMsg = 'Lỗi khi tải báo cáo của tôi.';
            if (jqXHR.status === 401) {
                errorMsg = 'Phiên đăng nhập hết hạn. Vui lòng tải lại trang và đăng nhập.';
            } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMsg = jqXHR.responseJSON.message;
            }
            container.html(`<div class="alert alert-danger">${htmlspecialchars(errorMsg)}</div>`);
        }
    });
}
// === HÀM XÓA BÁO CÁO ===
// Biến toàn cục để lưu ID báo cáo cần xóa
let reportIdToDelete = null;

// === HÀM BƯỚC 1: HIỆN MODAL XÁC NHẬN (Thay thế cho confirm()) ===
function deleteReport(reportId) {
    // 1. Lưu ID báo cáo vào biến toàn cục
    reportIdToDelete = reportId;

    // 2. Hiện Modal xác nhận tùy chỉnh
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
}

// === HÀM BƯỚC 2: THỰC HIỆN XÓA (Chạy khi bấm "Xóa Báo Cáo" trong Modal) ===
function confirmDeleteAction() {
    if (!reportIdToDelete) return;

    const modalEl = document.getElementById('deleteConfirmModal');
    const deleteModal = bootstrap.Modal.getInstance(modalEl);

    // Vô hiệu hóa nút và đổi chữ
    const confirmBtn = $('#btn-confirm-delete');
    confirmBtn.prop('disabled', true).text('Đang xóa...');

    // 1. Gửi AJAX (Code xóa giống hệt code cũ)
    $.ajax({
        url: 'api/delete_report.php',
        type: 'POST',
        data: JSON.stringify({
            report_id: reportIdToDelete
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Xóa card khỏi giao diện
                $(`#report-card-${reportIdToDelete}`).fadeOut(500, function() {
                    $(this).remove();
                    // Tải lại
                    if (typeof loadMyReports === 'function') {
                        loadMyReports();
                    } else if (typeof loadNewsfeed === 'function') {
                        loadNewsfeed('all');
                    }
                });

                // Đóng modal
                deleteModal.hide();

            } else {
                alert('Lỗi: ' + (response.message || 'Lỗi không xác định khi xóa.'));
            }
        },
        error: function(jqXHR) {
            alert('Lỗi máy chủ: ' + (jqXHR.responseJSON ? jqXHR.responseJSON.message : 'Vui lòng kiểm tra console.'));
        },
        complete: function() {
            // 2. Kích hoạt lại nút
            confirmBtn.prop('disabled', false).text('Xóa Báo Cáo');
            reportIdToDelete = null; // Xóa ID báo cáo đã lưu
        }
    });
}

// === HÀM HIỆN MODAL CHỈNH SỬA ===
function showEditModal(reportId) {
    // 1. Lấy dữ liệu đã "gài" ở hàm loadMyReports
    const card = $(`#report-card-${reportId}`);
    const categoryId = card.data('category-id');
    const description = card.data('description'); // Dùng data-description để lấy nội dung gốc

    // 2. Điền vào form trong modal "editReportModal"
    $('#edit_report_id').val(reportId);
    $('#edit_category_id').val(categoryId);
    $('#edit_description').val(description); // Đặt nội dung vào textarea

    // 3. Ẩn thông báo lỗi cũ
    $('#edit-report-alert').addClass('d-none').text('');

    // 4. Hiện modal
    const editModal = new bootstrap.Modal(document.getElementById('editReportModal'));
    editModal.show();
}

// === HÀM LƯU THAY ĐỔI (KHI BẤM NÚT TRONG MODAL) ===
function saveReportChanges() {
    const editForm = $('#editReportForm');
    const alertBox = $('#edit-report-alert');
    const saveBtn = $('#btn-save-edit');
    const editModal = bootstrap.Modal.getInstance(document.getElementById('editReportModal'));

    // 1. Lấy dữ liệu từ form
    const reportId = $('#edit_report_id').val();
    const categoryId = $('#edit_category_id').val();
    const description = $('#edit_description').val().trim();

    if (description === "") {
        alertBox.removeClass('d-none').addClass('alert-danger').text('Mô tả không được để trống.');
        return;
    }

    // 2. Vô hiệu hóa nút, hiển thị loading
    saveBtn.prop('disabled', true).text('Đang lưu...');
    alertBox.addClass('d-none');

    // 3. Gửi AJAX
    $.ajax({
        url: 'api/update_report.php', // File này sẽ được copy ở Bước 3
        type: 'POST',
        data: JSON.stringify({
            report_id: reportId,
            category_id: categoryId,
            description: description
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // 4. Thành công
                alertBox.removeClass('d-none').addClass('alert-success').text('Cập nhật thành công!');
                // Tải lại danh sách báo cáo để thấy thay đổi
                loadMyReports();

                // Tự động đóng modal
                setTimeout(() => {
                    editModal.hide();
                }, 1000);

            } else {
                alertBox.removeClass('d-none').addClass('alert-danger').text('Lỗi: ' + response.message);
            }
        },
        error: function(jqXHR) {
            // 5. Thất bại
            let errorMsg = 'Lỗi máy chủ.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMsg = jqXHR.responseJSON.message;
            } else if (jqXHR.status == 404) {
                errorMsg = 'Lỗi: Không tìm thấy api/update_report.php (xem Bước 3).';
            }
            alertBox.removeClass('d-none').addClass('alert-danger').text(errorMsg);
        },
        complete: function() {
            // 6. Kích hoạt lại nút
            saveBtn.prop('disabled', false).text('Lưu Thay Đổi');
        }
    });
}

// === HÀM HIỂN THỊ THÔNG BÁO TRONG MODAL ===
function showAlert(alertBox, message, type) {
    alertBox
        .removeClass('d-none alert-success alert-danger alert-info')
        .addClass(`alert-${type}`)
        .text(message);
}
// === HÀM GỬI BÁO CÁO (ĐANG BỊ TRỐNG) ===
function submitNewReport() {
    const form = $('#reportForm');
    const alertBox = $('#report-alert');
    const submitBtn = $('#btn-submit-report');

    // Lấy instance của Modal (để đóng sau khi thành công)
    const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));

    // 1. Lấy dữ liệu
    const categoryId = $('#category_id').val();
    const description = $('#description').val().trim();
    const imageFile = $('#report_image_input')[0].files[0];

    // 2. Validation (Kiểm tra phía client trước khi gửi)
    if (!imageFile) {
        showAlert(alertBox, 'Vui lòng tải lên ảnh sự cố.', 'danger');
        return;
    }
    if (!categoryId) {
        showAlert(alertBox, 'Vui lòng chọn một danh mục.', 'danger');
        return;
    }
    if (description === "") {
        showAlert(alertBox, 'Vui lòng nhập mô tả sự cố.', 'danger');
        return;
    }

    // 3. Vô hiệu hóa nút & hiển thị loading
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang gửi...');
    alertBox.addClass('d-none'); // Ẩn thông báo cũ

    // 4. Tạo FormData
    // Dùng $(form)[0] để lấy đối tượng DOM của form
    const formData = new FormData($(form)[0]);

    // 5. Gửi AJAX
    $.ajax({
        url: 'api/submit_report.php', // URL backend của bạn
        type: 'POST',
        data: formData,
        processData: false, // Bắt buộc khi gửi FormData
        contentType: false, // Bắt buộc khi gửi FormData
        dataType: 'json',
       success: function(response) {
            // 1. Đóng Modal + Reset Form (Giữ nguyên)
            reportModal.hide();
            $('#reportForm')[0].reset();
            $('#image_preview').hide().attr('src', '#');
            $('.image-preview-placeholder').show();
            $('#report-alert').addClass('d-none');

            // 2. CẬP NHẬT SỐ TRÊN CHUÔNG (Làm giả bằng JS, không gọi Server)
            let badge = $('#notification-count');
            // Lấy số hiện tại, nếu không có thì là 0
            let currentCount = parseInt(badge.text()) || 0;
            // Tăng lên 1 và hiện ra ngay
            badge.text(currentCount + 1).show();

            // 3. CHÈN THÔNG BÁO VÀO DANH SÁCH (Làm giả bằng JS)
            let notifList = $('#notification-list');
            
            // Xóa dòng "Không có thông báo" nếu đang có
            if (notifList.find('.text-center').length > 0) {
                notifList.empty();
            }
            
            // Tạo HTML thông báo mới
            let fakeHtml = `
                <li class="notification-unread">
                    <a class="dropdown-item notification-item" href="#">
                        <small class="d-block">Bài viết của bạn đã được gửi, hãy chờ duyệt</small>
                        <small class="text-muted">Vừa xong</small>
                    </a>
                </li>
            `;
            
            // Chèn vào đầu danh sách (prepend)
            notifList.prepend(fakeHtml);

            // 4. Reload Newsfeed bên dưới (Cái này cần thiết nên giữ lại)
            if (typeof loadNewsfeed === 'function') {
                loadNewsfeed('all');
            }

        },
        error: function(jqXHR) {
            // Xử lý lỗi (server 500, 400, 401)
            let errorMsg = 'Lỗi máy chủ. Vui lòng thử lại.';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                // Lấy thông báo lỗi từ file submit_report.php
                errorMsg = jqXHR.responseJSON.message;
            }
            showAlert(alertBox, errorMsg, 'danger');
        },
        complete: function() {
            // 6. Kích hoạt lại nút (dù thành công hay thất bại)
            submitBtn.prop('disabled', false).text('Gửi Báo Cáo');
        }
    });
}

// === BẮT ĐẦU THÊM MỚI (LEAFLET FUNCTIONS) ===
// 1. Hàm khởi tạo Leaflet Map (chỉ chạy 1 lần)
function initLeafletMap(centerPos) {
    if (document.getElementById('map-picker-leaflet') && !leafletMap) {
        leafletMap = L.map('map-picker-leaflet').setView([centerPos.lat, centerPos.lng], 16);

        // Dùng bản đồ nền OpenStreetMap (miễn phí)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(leafletMap);

        leafletMarker = L.marker([centerPos.lat, centerPos.lng], {
            draggable: true
        }).addTo(leafletMap);

        // Cập nhật input khi kéo thả
        leafletMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateHiddenInputs(pos);
        });

        // Cập nhật input lần đầu
        updateHiddenInputs(centerPos);
    }
}
// 2. Hàm di chuyển map và marker
function updateMapAndMarker(pos) {
    if (leafletMap && leafletMarker) {
        leafletMap.panTo(pos);
        leafletMarker.setLatLng(pos);
        updateHiddenInputs(pos);
    }
}
// 3. Hàm cập nhật input ẩn
function updateHiddenInputs(pos) {
    $('#latitude').val(pos.lat);
    $('#longitude').val(pos.lng);
}
// 4. Hàm chuyển đổi tọa độ EXIF (DMS sang DD)
function convertDMSToDD(degrees, minutes, seconds, direction) {
    let dd = degrees + (minutes / 60) + (seconds / 3600);
    if (direction == "S" || direction == "W") {
        dd = dd * -1;
    }
    return dd;
}
// 5. Hàm xem trước ảnh (tách ra từ code cũ của bạn)
function showImagePreview(file) {
    const imagePreview = document.getElementById('image_preview');
    const imagePreviewPlaceholder = document.querySelector('.image-preview-placeholder');
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (imagePreview) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            if (imagePreviewPlaceholder) imagePreviewPlaceholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    } else {
        if (imagePreview) {
            imagePreview.src = '#';
            imagePreview.style.display = 'none';
        }
        if (imagePreviewPlaceholder) imagePreviewPlaceholder.style.display = 'flex';
        if (file) alert('Vui lòng chọn một tệp ảnh hợp lệ (JPG, PNG, GIF).');
    }
}
// === KẾT THÚC THÊM MỚI (LEAFLET FUNCTIONS) ===
// === BẮT ĐẦU THÊM MỚI (BẢN ĐỒ TỔNG QUAN) ===
function loadSummaryMap() {
    const mapContainer = $('#map-summary');
    if (mapContainer.length === 0) return; // Chỉ chạy nếu tìm thấy div

    // 1. Xóa chữ "Google Map sẽ tải ở đây" (nếu có)
    mapContainer.empty(); 

    // 2. Khởi tạo bản đồ
    const map = L.map('map-summary', {
        scrollWheelZoom: true, // <<< SỬA: Cho phép zoom bằng cuộn chuột
        dragging: true,      // <<< SỬA: Cho phép kéo bản đồ
        zoomControl: true,   // <<< SỬA: Hiển thị nút (+) (-)
        attributionControl: false // Tắt chữ "Leaflet" (giữ nguyên)
    }).setView([10.7769, 106.7009], 12); // Zoom 12 (nhìn xa hơn 1 chút)

    // 3. Thêm Layer nền (SỬA: DÙNG NỀN OPENSTREETMAP CÓ MÀU)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
}
// === KẾT THÚC THÊM MỚI (BẢN ĐỒ TỔNG QUAN) ===

// === BẮT ĐẦU THÊM MỚI (TRANG MAP.PHP) ===
// === HÀM LOAD MAP VỚI BỘ LỌC (FILTER) ===
function loadMainMap() {
    const mapContainer = $('#full-map-container');
    if (mapContainer.length === 0) return;

    // 1. Hiển thị loading
    // Lưu ý: Không xóa nội dung cũ để giữ lại khung bộ lọc, chỉ hiện spinner đè lên hoặc bên trong
    if ($('#map-loading').length === 0) {
        mapContainer.append('<div id="map-loading" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); z-index:999;"><span class="spinner-border text-primary"></span></div>');
    }

    // 2. Khởi tạo bản đồ (Chỉ khởi tạo nếu chưa có)
    // Biến map và markerCluster cần khai báo ở phạm vi global hoặc check kỹ
    if (typeof mapInstance === 'undefined' || mapInstance === null) {
        // Khai báo biến global tạm thời cho map này nếu chưa có
        window.mapInstance = L.map('full-map-container', { zoomControl: false }).setView([10.7769, 106.7009], 13);
        
        // Di chuyển nút zoom sang góc khác cho đẹp (hoặc add lại sau)
        L.control.zoom({ position: 'topleft' }).addTo(window.mapInstance);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(window.mapInstance);
    }

    // --- [QUAN TRỌNG] KHÔNG CHÈN HTML BỘ LỌC BẰNG JS NỮA ---
    // (Vì file map.php đã có sẵn HTML đẹp rồi)

    // Định nghĩa Icon
    const LeafIcon = L.Icon.extend({
        options: {
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }
    });
    const greenIcon = new LeafIcon({iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png'});
    const goldIcon = new LeafIcon({iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png'});
    const redIcon = new LeafIcon({iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png'});

    // Biến lưu dữ liệu báo cáo và layer marker
    let allReportsData = [];
    // Reset marker cluster nếu đã tồn tại
    if (window.markerCluster) {
        window.markerCluster.clearLayers();
    } else {
        window.markerCluster = L.markerClusterGroup();
    }

    // 3. Hàm Vẽ Marker (Logic lọc chuẩn)
    function renderMarkers() {
        if (!window.markerCluster) return;
        window.markerCluster.clearLayers(); // Xóa marker cũ
        
        // Lấy giá trị từ bộ lọc
        const statusFilter = $('#filter-status').val();   
        const categoryFilter = $('#filter-category').val(); 

        // [DEBUG] In ra để xem mình đang chọn cái gì
        console.log("--- BẮT ĐẦU LỌC ---");
        console.log("Đang lọc theo Status:", statusFilter);
        console.log("Đang lọc theo Category ID:", categoryFilter);

        let count = 0;

        allReportsData.forEach(function(report) {
            // Kiểm tra dữ liệu đầu vào
            if (!report.latitude || !report.longitude) return;

            // --- 1. LOGIC LỌC TRẠNG THÁI ---
            let s = report.status ? report.status.toLowerCase() : '';
            let type = 'pending'; 
            if (s.includes('hoàn thành') || s.includes('xong') || s.includes('completed')) type = 'resolved';
            else if (s.includes('đang') || s.includes('xử lý') || s.includes('processing')) type = 'processing';
            
            if (statusFilter !== 'all' && statusFilter !== type) return;

            // --- 2. LOGIC LỌC DANH MỤC (SỬA LẠI CHỖ NÀY) ---
            if (categoryFilter !== 'all') {
                // Ép cả 2 về dạng CHUỖI (String) để so sánh cho chắc ăn
                // Ví dụ: "5" (từ HTML) sẽ bằng "5" (từ Database)
                let reportCatId = String(report.category_id); 
                let filterCatId = String(categoryFilter);

                if (reportCatId !== filterCatId) {
                    return; // Không khớp thì bỏ qua
                }
            }

            // --- NẾU CHẠY ĐẾN ĐÂY LÀ ĐÃ KHỚP ---
            
            // Chọn Icon
            let finalIcon = redIcon;
            let statusBadge = '<span class="badge bg-danger mb-2">Mới tiếp nhận</span>';
            
            if (type === 'resolved') {
                finalIcon = greenIcon;
                statusBadge = '<span class="badge bg-success mb-2">Đã khắc phục</span>';
            } else if (type === 'processing') {
                finalIcon = goldIcon;
                statusBadge = '<span class="badge bg-warning text-dark mb-2">Đang xử lý</span>';
            }

            const popupContent = `
                <div style="width: 220px;">
                    ${statusBadge}
                    <img src="${htmlspecialchars(report.image_url)}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 8px;">
                    <h6 style="font-weight: bold; margin-bottom: 2px;">${htmlspecialchars(report.category_name || 'Sự cố')}</h6>
                    <p style="font-size: 0.85rem; margin-bottom: 5px; color: #555;">${htmlspecialchars(report.description.substring(0, 80))}...</p>
                    <small class="text-muted"><i class="fas fa-clock"></i> ${formatTimeAgo(report.created_at)}</small>
                </div>
            `;

            const marker = L.marker([report.latitude, report.longitude], {icon: finalIcon});
            marker.bindPopup(popupContent);
            window.markerCluster.addLayer(marker);
            count++;
        });

        window.mapInstance.addLayer(window.markerCluster);
        console.log("Đã vẽ xong: " + count + " điểm.");
    }
    // 4. Lấy dữ liệu từ Server
    $.ajax({
        url: 'api/get_reports.php',
        type: 'GET',
        dataType: 'json',
        success: function(reports) {
            $('#map-loading').remove(); // Tắt loading
            allReportsData = reports; // Lưu dữ liệu vào biến

            // --- CẬP NHẬT SELECT DANH MỤC TỰ ĐỘNG ---
            // Code này sẽ xóa các option cứng trong HTML (trừ cái đầu tiên) 
            // và điền lại danh mục CHÍNH XÁC có trong database
           
            // ----------------------------------------------

            // Vẽ lần đầu
            renderMarkers();

            // Gắn sự kiện: Khi chọn filter thì vẽ lại (Dùng off trước để tránh gán trùng lặp)
            $('#filter-status, #filter-category').off('change').on('change', function() {
                console.log("Bộ lọc thay đổi:", $(this).val());
                renderMarkers();
            });
        },
        error: function() {
            $('#map-loading').html('<div class="alert alert-danger m-3">Lỗi tải dữ liệu.</div>');
        }
    });
}
// === KẾT THÚC THÊM MỚI (TRANG MAP.PHP) ===

// === BẮT ĐẦU THÊM MỚI: HÀM LOGIC CHO CHATBOT ===
// (Biến này nên được khai báo ở đầu script)
let chatbotSelectedFile = null;

function sendChatMessage() {
    let input = $('#chatbot-input');
    let message = input.val().trim();

    // Sửa: Phải có tin nhắn HOẶC ảnh thì mới gửi
    if (message === "" && !chatbotSelectedFile) return;
    $('#chatbot-image-preview-area').hide();

    // Lấy avatar của user hiện tại
    const userAvatarHtml = `<div class="comment-avatar"><img src="${htmlspecialchars(currentUserAvatarUrl)}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"></div>`;

    input.val('').prop('disabled', true);
    $('#chatbot-send-btn').prop('disabled', true);
    $('#chatbot-upload-image-btn').prop('disabled', true); // Vô hiệu hóa nút ảnh

    // Hiển thị tin nhắn của User
    renderChatMessage(message, 'user', userAvatarHtml);

    // Hiển thị ảnh nếu có
    if (chatbotSelectedFile) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let imgHtml = `<img src="${e.target.result}" alt="Ảnh gửi" style="max-width: 150px; border-radius: 8px; margin-top: 5px;">`;
            // Thêm ảnh vào tin nhắn của user (bong bóng chat cuối cùng)
            $('.chat-message.user').last().find('.comment-text').append(imgHtml);
            $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight);
        };
        reader.readAsDataURL(chatbotSelectedFile);
    }

    // Hiển thị "Bot đang gõ..."
    renderChatMessage("Bot đang gõ...", 'typing');

    // === SỬA LỖI: Gửi AJAX bằng FormData để gửi cả ảnh và text ===
    let formData = new FormData();
    formData.append('message', message);
    if (chatbotSelectedFile) {
        formData.append('chat_image', chatbotSelectedFile); // Tên file là 'chat_image'
    }

    $.ajax({
        url: 'api/chatbot_query.php', // API cần được nâng cấp
        type: 'POST',
        data: formData, // Gửi FormData
        processData: false, // Bắt buộc
        contentType: false, // Bắt buộc
        dataType: 'json',
        success: function(response) {
            $('.chat-message.typing').remove();
            renderChatMessage(response.reply, 'bot');
        },
        error: function(jqXHR) {
            $('.chat-message.typing').remove();
            let errorMsg = (jqXHR.responseJSON && jqXHR.responseJSON.reply) ? jqXHR.responseJSON.reply : "Lỗi máy chủ, không thể kết nối Bot.";
            renderChatMessage(errorMsg, 'bot', true);
        },
        complete: function() {
            // Kích hoạt lại input
            input.prop('disabled', false).focus();
            $('#chatbot-send-btn').prop('disabled', false);
            $('#chatbot-upload-image-btn').prop('disabled', false);
            // Xóa ảnh đã chọn
            chatbotSelectedFile = null;
            $('#chatbot-image-input').val('');
            $('#chatbot-image-preview-area').hide();
        }
    });
}

// 3. Hàm hiển thị tin nhắn lên giao diện
function renderChatMessage(message, type, avatarHtml = null, isError = false) {
    let chatBody = $('#chatbot-body');
    let contentClass = 'comment-content';
    let name = 'Bot Hỗ trợ';

    if (type === 'user') {
        // avatarHtml đã được truyền vào
        name = <?php echo json_encode($_SESSION['user_fullname'] ?? 'User'); ?> ;
    } else if (type === 'bot') {
        avatarHtml = `<div class="comment-avatar"><i class="fas fa-robot"></i></div>`;
        if (isError) contentClass += ' bg-danger text-white'; // Báo lỗi
    } else if (type === 'typing') {
        let botAvatar = `<div class="comment-avatar"><i class="fas fa-robot"></i></div>`;
        chatBody.append(`<div class="chat-message typing">${botAvatar} ${message}</div>`);
        chatBody.scrollTop(chatBody[0].scrollHeight); // Cuộn xuống
        return;
    }

    let messageHtml = `
        <div class="chat-message ${type}">
            ${avatarHtml}
            <div class="${contentClass}">
                <span class="comment-user-name">${htmlspecialchars(name)}</span>
                <div class="comment-text">${htmlspecialchars(message)}</div>
            </div>
        </div>
    `;
    chatBody.append(messageHtml);
    chatBody.scrollTop(chatBody[0].scrollHeight); // Tự động cuộn xuống tin nhắn mới nhất
}
// === KẾT THÚC THÊM MỚI (CHATBOT) ===


// ----- Initialization / $(document).ready() -----
$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const categoryFilter = urlParams.get('category') || 'all';

    if (window.location.pathname.endsWith('profile.php')) {
        loadMyReports();

        // === THÊM KHỐI NÀY VÀO ===
    } else if (window.location.pathname.endsWith('map.php')) {
        loadMainMap(); // Gọi hàm mới
    } else if (window.location.pathname.endsWith('index.php') || window.location.pathname.includes('/DOAN_CN/')) {
        if ($('#newsfeed-container').length > 0) {
        loadNewsfeed(categoryFilter);
        }
        if ($('#map-summary').length > 0) {
            loadSummaryMap(); 
        }
        $('.nav-sidebar a.nav-link.active').removeClass('active');
        if (categoryFilter === 'all') {
            $('.nav-sidebar a[href="index.php"]').addClass('active');
        } else {
            $(`.nav-sidebar a[href="index.php?category=${categoryFilter}"]`).addClass('active');
        }
        // === KẾT THÚC THÊM MỚI ===

    }
    $('#btn-save-edit').on('click', saveReportChanges);
    $('#btn-submit-report').on('click', submitNewReport);

    // === JAVASCRIPT XEM TRƯỚC ẢNH ===
    const reportImageInput = document.getElementById('report_image_input');
    const imagePreview = document.getElementById('image_preview');
    const imagePreviewBox = document.getElementById('image_preview_box');
    const imagePreviewPlaceholder = imagePreviewBox ? imagePreviewBox.querySelector('.image-preview-placeholder') : null;

   let currentXhr = null;

if (reportImageInput) {
    reportImageInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        const catSelect = $('#category_id'); 
        const firstOption = catSelect.find('option:first');
        
        // Lưu lại chữ gốc "-- Vui lòng chọn --" để dùng sau này
        if (!catSelect.data('original-text')) {
            catSelect.data('original-text', firstOption.text());
        }
        const originalText = catSelect.data('original-text');

        // 1. HỦY NGAY REQUEST CŨ (Nếu người dùng chọn ảnh liên tục)
        if (currentXhr) {
            currentXhr.abort();
            currentXhr = null;
        }

        // Hiện ảnh preview
        showImagePreview(file); 

        if (file) {
            // 2. KHÓA GIAO DIỆN & HIỆN TRẠNG THÁI LOADING
            let isSuccess = false; // Mặc định là chưa thành công

            catSelect.val('').trigger('change'); // Xóa trắng ô chọn
            catSelect.prop('disabled', true);    // Khóa không cho bấm
            catSelect.css('opacity', '0.6');     // Làm mờ
            firstOption.text("⏳ Đang hỏi AI..."); // Đổi chữ

            let formData = new FormData();
            formData.append('image', file);

            // Gán request vào biến để quản lý
            currentXhr = $.ajax({
                url: 'api/analyze_image.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                timeout: 20000, // 20 giây timeout
                success: function(res) {
                    // === TRƯỜNG HỢP THÀNH CÔNG ===
                    if (res.success && res.suggested_id) {
                        isSuccess = true; // BẬT CHỐT AN TOÀN
                        
                        console.log("AI chốt ID:", res.suggested_id);
                        
                        // 1. Trả lại chữ gốc ngay lập tức (Để nó không hiện 'Đang hỏi AI' nữa)
                        firstOption.text(originalText);
                        
                        // 2. Chọn đúng ID
                        catSelect.val(res.suggested_id).trigger('change');
                        
                        // 3. Hiệu ứng xanh báo thành công
                        catSelect.css({'border': '2px solid #28a745', 'box-shadow': '0 0 10px #28a745'});
                        setTimeout(() => catSelect.css({'border': '', 'box-shadow': ''}), 2000);
                    }
                },
                error: function(xhr, status) {
                    // Nếu lỗi do mình tự hủy (abort) thì kệ nó, không làm gì cả
                    if (status === 'abort') return;
                    console.log("Lỗi AI:", status);
                },
                complete: function() {
                    // === HÀM DỌN DẸP (LUÔN CHẠY SAU CÙNG) ===
                    
                    // Mở khóa ô chọn
                    catSelect.prop('disabled', false); 
                    catSelect.css('opacity', '1');
                    
                    // QUAN TRỌNG: Chỉ reset chữ nếu THẤT BẠI
                    // Nếu isSuccess = true (đã chọn được Cây đổ) thì GIỮ NGUYÊN, cấm đụng vào!
                    if (!isSuccess) {
                        firstOption.text(originalText); // Trả về "-- Vui lòng chọn --"
                    }
                    
                    currentXhr = null; // Xóa biến request
                }
            });
        }
   

            if (file) { // Chỉ chạy EXIF nếu có file
                EXIF.getData(file, function() {
                    const lat = EXIF.getTag(this, "GPSLatitude");
                    const lng = EXIF.getTag(this, "GPSLongitude");
                    const latRef = EXIF.getTag(this, "GPSLatitudeRef");
                    const lngRef = EXIF.getTag(this, "GPSLongitudeRef");

                    if (lat && lng && latRef && lngRef) {
                        const decimalLat = convertDMSToDD(lat[0], lat[1], lat[2], latRef);
                        const decimalLng = convertDMSToDD(lng[0], lng[1], lng[2], lngRef);
                        const photoPos = {
                            lat: decimalLat,
                            lng: decimalLng
                        };

                        alert('Đã tìm thấy vị trí GPS từ ảnh của bạn!');
                        $(reportImageInput).data('gps-found', true); // Đặt cờ đã tìm thấy

                        if (!leafletMap) {
                            initLeafletMap(photoPos);
                        } else {
                            updateMapAndMarker(photoPos);
                        }
                    }
                });
            } else {
                // Nếu file không hợp lệ (file=null), hàm showImagePreview đã xử lý, 
                // và ta cũng nên reset input
                reportImageInput.value = '';
            }
            // === KẾT THÚC THAY ĐỔI (ĐỌC EXIF) ===
        });
        const reportModalEl = document.getElementById('reportModal');
        if (reportModalEl) {
            reportModalEl.addEventListener('hidden.bs.modal', function() {
                if (imagePreview) {
                    imagePreview.src = '#';
                    imagePreview.style.display = 'none';
                }
                if (imagePreviewPlaceholder) imagePreviewPlaceholder.style.display = 'flex';
                if (reportImageInput) reportImageInput.value = '';
                $('#report-alert').addClass('d-none');

                // === BẮT ĐẦU THÊM MỚI (RESET MAP) ===
                $('#report_image_input').data('gps-found', false);
                if (leafletMap) updateMapAndMarker(defaultCoordsLeaflet);
                // === KẾT THÚC THÊM MỚI ===

                const reportForm = document.getElementById('reportForm');
                if (reportForm) reportForm.reset();
            });
        }
    }

    // === BẮT ĐẦU THÊM MỚI: GẮN SỰ KIỆN CHO CHATBOT ===
    // (Vì HTML của chatbot đã ở trên, code này giờ sẽ chạy đúng)
    $('#chatbot-toggle-btn').on('click', function() {
        $('#chatbot-container').fadeToggle(300).css('display', 'flex');
    });
    $('#chatbot-close-btn').on('click', function() {
        $('#chatbot-container').fadeOut(300);
    });
    $('#chatbot-send-btn').on('click', function() {
        sendChatMessage();
    });
    $('#chatbot-input').on('keypress', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            sendChatMessage();
        }
    });
    // Xử lý nút tải ảnh chatbot
    $('#chatbot-upload-image-btn').on('click', function() {
        $('#chatbot-image-input').click();
    });
    $('#chatbot-image-input').on('change', function(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            chatbotSelectedFile = file; // Lưu file vào biến tạm
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#chatbot-selected-image').attr('src', e.target.result);
                $('#chatbot-image-preview-area').show();
            }
            reader.readAsDataURL(file);
        } else {
            chatbotSelectedFile = null;
        }
    });
    $('#chatbot-remove-image-btn').on('click', function() {
        chatbotSelectedFile = null;
        $('#chatbot-image-input').val('');
        $('#chatbot-image-preview-area').hide();
    });
    // === KẾT THÚC THÊM MỚI (CHATBOT) ===

/* --- BẮT ĐẦU: CODE THÔNG BÁO --- */

    // === HÀM TẢI THÔNG BÁO ===
    function loadNotifications() {
        $.ajax({
            url: 'api/get_notifications.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const notifList = $('#notification-list');
                    const countBadge = $('#notification-count');
                    
                    notifList.empty(); // Xóa chữ "Đang tải..."

                    // 1. Hiển thị số lượng chưa đọc
                    if (response.unread_count > 0) {
                        countBadge.text(response.unread_count).show();
                    } else {
                        countBadge.hide();
                    }

                    // 2. Hiển thị danh sách thông báo
                    if (response.notifications.length === 0) {
                        notifList.html('<li><p class="text-center p-3 text-muted mb-0">Không có thông báo nào.</p></li>');
                    } else {
                        response.notifications.forEach(function(notif) {
                            // Dùng hàm formatTimeAgo() bạn đã có
                            let timeAgo = formatTimeAgo(notif.created_at); 
                            let itemClass = notif.is_read == 0 ? 'notification-unread' : '';
                            // Sửa link: Bỏ ../ vì link này dùng ở trang chủ
                            let link = notif.link ? `href="${notif.link.replace('../', '')}"` : 'href="#"'; 
                            
                            let html = `
                                <li class="${itemClass}">
                                    <a class="dropdown-item notification-item" ${link} data-id="${notif.id}">
                                        <small class="d-block">${htmlspecialchars(notif.message)}</small>
                                        <small class="text-muted">${timeAgo}</small>
                                    </a>
                                </li>
                            `;
                            notifList.append(html);
                        });
                    }
                }
            },
            error: function() {
                $('#notification-list').html('<li><p class="text-danger p-3 mb-0">Lỗi tải thông báo.</p></li>');
            }
        });
    }

    // === HÀM ĐÁNH DẤU ĐÃ ĐỌC ===
    function markNotificationsAsRead() {
        // Chỉ gửi yêu cầu nếu có thông báo chưa đọc
        if ($('#notification-count').is(':visible')) {
            $.ajax({
                url: 'api/mark_notifications_read.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#notification-count').fadeOut(); // Ẩn đi
                        // Xóa class "unread" khỏi tất cả thông báo
                        $('#notification-list .notification-unread').removeClass('notification-unread');
                    }
                }
            });
        }
    }

    // === GỌI HÀM KHI TẢI TRANG ===
    // Chỉ tải nếu người dùng đã đăng nhập (kiểm tra xem icon chuông có tồn tại không)
    if ($('#notification-dropdown-container').length > 0) {
        // Tải thông báo sau 1 giây để không làm chậm trang
        setTimeout(loadNotifications, 1000); 
        
        // Gắn sự kiện: Khi nhấp vào chuông thì gọi hàm "đã đọc"
        $('#notification-bell').on('click', function() {
            // Chờ 1.5s (khi dropdown mở ra) rồi mới đánh dấu đã đọc
            setTimeout(markNotificationsAsRead, 1500); 
        });
        
        // Tự động kiểm tra thông báo mới mỗi 1 phút
        setInterval(loadNotifications, 60000); 
    }

    /* --- KẾT THÚC: CODE THÔNG BÁO --- */


    /* --- BẮT ĐẦU: CODE UPLOAD AVATAR CHO profile.php --- */
    // ... (code upload avatar cũ của bạn) ...
    /* --- BẮT ĐẦU: CODE UPLOAD AVATAR CHO profile.php --- */
    if ($('#avatar-upload-input').length > 0) {

        const avatarInput = $('#avatar-upload-input');
        const avatarModal = new bootstrap.Modal(document.getElementById('avatarModal'));
        const avatarPreview = $('#avatar-preview-img');
        const currentAvatarImg = $('#current-avatar-img');
        const saveAvatarBtn = $('#save-avatar-button');
        const avatarAlert = $('#avatar-upload-alert');
        let selectedFile = null;

        avatarInput.on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Vui lòng chỉ chọn file ảnh (JPG, PNG, GIF).');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    alert('File quá lớn, vui lòng chọn file dưới 5MB.');
                    return;
                }
                selectedFile = file;
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.attr('src', e.target.result);
                    avatarModal.show();
                }
                reader.readAsDataURL(file);
            }
        });

        saveAvatarBtn.on('click', function() {
            if (!selectedFile) return;

            $(this).prop('disabled', true).text('Đang lưu...');
            avatarAlert.removeClass('d-none alert-danger alert-success').addClass('alert-info').text('Đang tải file lên, vui lòng chờ...');

            const formData = new FormData();
            formData.append('avatar', selectedFile);

            $.ajax({
                url: 'api/upload_avatar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const newAvatarUrl = response.new_avatar_url + '?' + new Date().getTime();

                        currentAvatarImg.attr('src', newAvatarUrl);
                        $('#header-avatar').attr('src', newAvatarUrl);

                        avatarAlert.removeClass('alert-info').addClass('alert-success').text(response.message);

                        setTimeout(function() {
                            avatarModal.hide();
                        }, 1500);

                    } else {
                        avatarAlert.removeClass('alert-info').addClass('alert-danger').text(response.message);
                    }
                },
                error: function() {
                    avatarAlert.removeClass('alert-info').addClass('danger').text('Lỗi máy chủ. Không thể tải ảnh lên.');
                },
                complete: function() {
                    saveAvatarBtn.prop('disabled', false).text('Lưu thay đổi');
                    avatarInput.val('');
                    selectedFile = null;
                }
            });
        });

        $('#avatarModal').on('hidden.bs.modal', function() {
            avatarAlert.addClass('d-none').removeClass('alert-danger alert-success alert-info');
            saveAvatarBtn.prop('disabled', false).text('Lưu thay đổi');
        });
    }

    /* --- BẮT ĐẦU: KÍCH HOẠT BẢN ĐỒ KHI MỞ MODAL --- */
    $('#reportModal').on('shown.bs.modal', function() {
        if (!leafletMap) {
            // Khởi tạo map
            initLeafletMap(defaultCoordsLeaflet);
        } else {
            // Resize
            leafletMap.invalidateSize();
        }

        // Luôn kiểm tra vị trí trình duyệt nếu ảnh chưa có GPS
        if (navigator.geolocation && !$('#report_image_input').data('gps-found')) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const userPos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                updateMapAndMarker(userPos);
            }, function() {
                // Lỗi, giữ vị trí mặc định
            });
        }
    });
    /* --- KẾT THÚC: KÍCH HOẠT BẢN ĐỒ --- */
$(document).ready(function() {
    // 1. Khi bấm vào nút Robot (Mở chat) -> Ẩn nút Robot đi
    $('#chatbot-toggle-btn').on('click', function() {
        $('#chatbot-floating-container').fadeOut(); 
    });

    // 2. Khi bấm vào nút X (Đóng chat) -> Hiện lại nút Robot
    // Tao dùng đúng cái ID #chatbot-close-btn trong ảnh mày gửi
    $(document).on('click', '#chatbot-close-btn', function() {
        $('#chatbot-floating-container').fadeIn();
    });
});

    $('#btn-confirm-delete').on('click', confirmDeleteAction);
    /* --- KẾT THÚC: CODE UPLOAD AVATAR --- */
}); // <-- Đây là thẻ đóng của $(document).ready()
</script>
</body>

</html>