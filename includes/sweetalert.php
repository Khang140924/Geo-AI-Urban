<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Hàm hiển thị thông báo
    function showToast(icon, title) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: icon,
            title: title
        });
    }

    // Kiểm tra URL có tham số 'success' không
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');

    if (successMsg) {
        // Nếu là thông báo lớn (như đăng nhập thành công), dùng Popup giữa màn hình
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: successMsg,
            confirmButtonColor: '#00A78E'
        });
        // Xóa tham số trên URL để F5 không bị hiện lại
        window.history.replaceState(null, null, window.location.pathname);
    }

    if (errorMsg) {
        // Nếu là lỗi, hiện Popup báo lỗi
        Swal.fire({
            icon: 'error',
            title: 'Opps...',
            text: errorMsg,
            confirmButtonColor: '#d33'
        });
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>