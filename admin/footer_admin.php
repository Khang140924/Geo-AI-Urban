</main>
<div id="chatbot-floating-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button class="btn btn-primary rounded-circle shadow-lg d-flex justify-content-center align-items-center" 
            id="chatbot-toggle-btn"
            style="width: 60px; height: 60px; font-size: 24px; border: none; background-color: #00A78E;">
        <i class="fas fa-robot text-white"></i>
    </button>
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
                <div class="comment-text">Chào Admin, tôi có thể giúp gì cho bạn? (VD: "có bao nhiêu báo cáo chờ duyệt")</div>
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
        <input type="text" class="form-control" id="chatbot-input" placeholder="Nhập câu hỏi...">
        <button class="btn btn-primary btn-send-comment" id="chatbot-send-btn" style="background-color: #00A78E; border-color: #00A78E;">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>
<script>
// --- BIẾN TOÀN CỤC CẦN THIẾT ---
let chatbotSelectedFile = null;
// Lấy avatar của Admin từ session (file header_admin.php đã nạp)
const currentUserAvatarUrl = '<?php echo htmlspecialchars($_SESSION['user_avatar_url'] ?? "https_YOUR_DEFAULT_AVATAR_URL"); ?>';

// --- HÀM GỬI TIN NHẮN (ĐÃ SỬA ĐƯỜNG DẪN API) ---
function sendChatMessage() {
    let input = $('#chatbot-input');
    let message = input.val().trim();
    if (message === "" && !chatbotSelectedFile) return;
    $('#chatbot-image-preview-area').hide();

    const userAvatarHtml = `<div class="comment-avatar"><img src="${htmlspecialchars(currentUserAvatarUrl)}" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"></div>`;
    input.val('').prop('disabled', true);
    $('#chatbot-send-btn').prop('disabled', true);
    $('#chatbot-upload-image-btn').prop('disabled', true);

    renderChatMessage(message, 'user', userAvatarHtml);

    if (chatbotSelectedFile) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let imgHtml = `<img src="${e.target.result}" alt="Ảnh gửi" style="max-width: 150px; border-radius: 8px; margin-top: 5px;">`;
            $('.chat-message.user').last().find('.comment-text').append(imgHtml);
            $('#chatbot-body').scrollTop($('#chatbot-body')[0].scrollHeight);
        };
        reader.readAsDataURL(chatbotSelectedFile);
    }

    renderChatMessage("Bot đang gõ...", 'typing');

    let formData = new FormData();
    formData.append('message', message);
    if (chatbotSelectedFile) {
        formData.append('chat_image', chatbotSelectedFile);
    }

    $.ajax({
        // ===============================================
        // SỬA LỖI ĐƯỜNG DẪN QUAN TRỌNG
        // ===============================================
        url: '../api/chatbot_query.php', // Thêm ../
        // ===============================================
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
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
            input.prop('disabled', false).focus();
            $('#chatbot-send-btn').prop('disabled', false);
            $('#chatbot-upload-image-btn').prop('disabled', false);
            chatbotSelectedFile = null;
            $('#chatbot-image-input').val('');
            $('#chatbot-image-preview-area').hide();
        }
    });
}

// --- HÀM HIỂN THỊ TIN NHẮN ---
function renderChatMessage(message, type, avatarHtml = null, isError = false) {
    let chatBody = $('#chatbot-body');
    let contentClass = 'comment-content';
    let name = 'Bot Hỗ trợ';

    if (type === 'user') {
        name = <?php echo json_encode($_SESSION['user_fullname'] ?? 'User'); ?> ;
    } else if (type === 'bot') {
        avatarHtml = `<div class="comment-avatar"><i class="fas fa-robot"></i></div>`;
        if (isError) contentClass += ' bg-danger text-white';
    } else if (type === 'typing') {
        let botAvatar = `<div class="comment-avatar"><i class="fas fa-robot"></i></div>`;
        chatBody.append(`<div class="chat-message typing">${botAvatar} ${message}</div>`);
        chatBody.scrollTop(chatBody[0].scrollHeight);
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
    chatBody.scrollTop(chatBody[0].scrollHeight);
}

// --- GẮN SỰ KIỆN (Nên đặt trong document.ready) ---
$(document).ready(function() {
    // Ẩn nút chat khi bấm mở
    $('#chatbot-toggle-btn').on('click', function() {
        $('#chatbot-container').fadeToggle(300).css('display', 'flex');
        $('#chatbot-floating-container').fadeOut();
    });
    // Hiện nút chat khi bấm đóng
    $('#chatbot-close-btn').on('click', function() {
        $('#chatbot-container').fadeOut(300);
        $('#chatbot-floating-container').fadeIn();
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
    // Xử lý nút tải ảnh
    $('#chatbot-upload-image-btn').on('click', function() {
        $('#chatbot-image-input').click();
    });
    $('#chatbot-image-input').on('change', function(event) {
        const file = event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            chatbotSelectedFile = file;
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
});
</script> 
</body>
</html>