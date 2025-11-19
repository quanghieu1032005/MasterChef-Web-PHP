// Admin Panel JavaScript

// Confirm Delete Action
function confirmDelete(message = 'Bạn có chắc chắn muốn xóa mục này không?') {
    return confirm(message);
}

// Image Preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            $('#' + previewId).attr('src', e.target.result).show();
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Toggle User Block Status
function toggleUserStatus(userId, isBlocked, csrfToken) {
    if (confirm('Bạn có chắc chắn muốn ' + (isBlocked ? 'bỏ chặn' : 'chặn') + ' người dùng này?')) {
        $.ajax({
            url: 'user_toggle_status.php',
            type: 'POST',
            dataType: 'json',
            data: {
                user_id: userId,
                status: isBlocked ? 0 : 1,
                csrf_token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Đã xảy ra lỗi. Vui lòng thử lại sau.');
            }
        });
    }
}

// Create Slug from Title
function createSlug(title) {
    // Chuyển đổi tiếng Việt có dấu thành không dấu
    var slug = title.toLowerCase();
    
    // Chuyển các ký tự đặc biệt thành dấu gạch ngang
    slug = slug.replace(/[^\w ]+/g, '');
    
    // Thay thế khoảng trắng bằng dấu gạch ngang
    slug = slug.replace(/ +/g, '-');
    
    return slug;
}

// Auto-generate slug from title
$(document).ready(function() {
    $('#title').on('keyup', function() {
        var title = $(this).val();
        var slug = createSlug(title);
        $('#slug').val(slug);
    });
    
    // Image preview for file inputs
    $('input[type="file"]').change(function() {
        var previewId = $(this).data('preview');
        if (previewId) {
            previewImage(this, previewId);
        }
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
    // Initialize select2 for enhanced select boxes
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            placeholder: 'Chọn...',
            allowClear: true
        });
    }
    
    // Ajax form submission with validation
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var formData = new FormData(form[0]);
        
        // Disable submit button during ajax request
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.reload) {
                        window.location.reload();
                    } else {
                        // Display success message
                        form.find('.alert-container').html('<div class="alert alert-success">' + response.message + '</div>');
                        
                        // Reset form if specified
                        if (response.reset_form) {
                            form[0].reset();
                        }
                    }
                } else {
                    // Display error message
                    form.find('.alert-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                form.find('.alert-container').html('<div class="alert alert-danger">Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.</div>');
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(submitBtn.data('original-text') || 'Lưu');
            }
        });
    });
}); 