$(document).ready(function() {
    // Khởi tạo Carousel (slideshow)
    var carousel = $('.carousel');
    if (carousel.length) {
        carousel.carousel({
            interval: 5000,
            pause: "hover"
        });
    }
    
    // Flag để kiểm soát việc submit form
    var isSubmitting = false;
    
    // Xử lý form bình luận
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Ngăn submit nhiều lần
        if (isSubmitting) return false;
        isSubmitting = true;
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var formData = form.serialize();
        
        // Disable nút submit trong khi xử lý
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang gửi...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Hiển thị bình luận mới
                    $('#commentList').prepend(response.html);
                    
                    // Reset form
                    form[0].reset();
                    
                    // Cập nhật số lượng bình luận
                    var commentCount = parseInt($('#commentsSection .card-header h5').text().match(/\d+/)[0]) + 1;
                    $('#commentsSection .card-header h5').text('Bình luận (' + commentCount + ')');
                    
                    // Cập nhật số lượng bình luận ở metadata
                    var metaCommentLink = $('.recipe-meta .comment-link');
                    if (metaCommentLink.length) {
                        var metaCommentText = metaCommentLink.html().replace(/\d+/, commentCount);
                        metaCommentLink.html(metaCommentText);
                    }
                    
                    // Ẩn thông báo "chưa có bình luận" nếu có
                    $('#commentList .text-center.text-muted.py-3').hide();
                } else {
                    // Hiển thị thông báo lỗi
                    alert(response.message || 'Đã xảy ra lỗi khi gửi bình luận. Vui lòng thử lại.');
                }
            },
            // error: function() {
            //     alert('Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.');
            // },
            complete: function() {
                // Enable lại nút submit và reset flag
                submitBtn.prop('disabled', false).html('<i class="far fa-paper-plane me-1"></i> Gửi bình luận');
                isSubmitting = false;
            }
        });
    });
    
    // Xác nhận xóa
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Bạn có chắc chắn muốn xóa mục này không?')) {
            e.preventDefault();
        }
    });
    
    // Back to Top Button
    var backToTopBtn = $('#backToTop');
    
    // Show/hide the button based on scroll position
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            backToTopBtn.fadeIn();
        } else {
            backToTopBtn.fadeOut();
        }
    });
    
    // Smooth scroll to top when clicked
    backToTopBtn.click(function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 800);
        return false;
    });
    
    // Add scroll-margin-top for comment section hash links
    $('.comment-link').on('click', function(e) {
        // Add some top margin when scrolling to comment section to account for fixed header
        $('#commentsSection').css('scroll-margin-top', '80px');
        
        // If already on the same page, handle smooth scrolling manually
        if(window.location.pathname + window.location.search === $(this).attr('href').split('#')[0]) {
            e.preventDefault();
            var target = $($(this).attr('href'));
            if(target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 60
                }, 800);
                return false;
            }
        }
    });
}); 
