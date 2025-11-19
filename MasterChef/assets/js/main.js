// Back to Top Button
document.addEventListener('DOMContentLoaded', function() {
    // Back to Top Button
    var backToTopButton = document.getElementById('backToTop');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    }
    
    // Ajax comment submission
    var commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Thêm comment mới vào DOM
                    var commentList = document.getElementById('commentList');
                    if (commentList.querySelector('.text-center.text-muted')) {
                        commentList.innerHTML = '';
                    }
                    
                    var newComment = document.createElement('div');
                    newComment.innerHTML = data.html;
                    commentList.insertBefore(newComment.firstChild, commentList.firstChild);
                    
                    // Xóa nội dung textarea
                    document.getElementById('comment').value = '';
                    
                    // Hiển thị thông báo thành công
                    var alertSuccess = document.createElement('div');
                    alertSuccess.className = 'alert alert-success';
                    alertSuccess.textContent = data.message;
                    
                    var formContainer = commentForm.parentNode;
                    formContainer.insertBefore(alertSuccess, commentForm);
                    
                    // Xóa thông báo sau 3 giây
                    setTimeout(function() {
                        alertSuccess.remove();
                    }, 3000);
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Hiển thị thông báo lỗi
                        var alertError = document.createElement('div');
                        alertError.className = 'alert alert-danger';
                        alertError.textContent = data.message;
                        
                        var formContainer = commentForm.parentNode;
                        formContainer.insertBefore(alertError, commentForm);
                        
                        // Xóa thông báo sau 3 giây
                        setTimeout(function() {
                            alertError.remove();
                        }, 3000);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Favorites functionality
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            
            fetch(SITE_URL + '/ajax/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle the heart icon and active class
                    const icon = this.querySelector('i');
                    if (data.is_favorite) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.classList.add('active');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.classList.remove('active');
                    }
                    
                    // Show notification
                    const notification = document.createElement('div');
                    notification.className = 'position-fixed top-0 end-0 p-3';
                    notification.style.zIndex = '5000';
                    
                    notification.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header">
                                <strong class="me-auto">Thông báo</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                ${data.message}
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(notification);
                    
                    // Remove notification after 3 seconds
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
}); 
// --- LOGIC CHATBOT ---
function toggleChat() {
    const chatWindow = document.getElementById('chat-widget-window');
    const chatBtn = document.getElementById('chat-widget-btn');
    const chatCallout = document.getElementById('chat-callout');
    
    if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
        chatWindow.style.display = 'flex';
        chatBtn.style.display = 'none';
        if(chatCallout) chatCallout.style.display = 'none';
        setTimeout(() => document.getElementById('chat-input').focus(), 100);
    } else {
        chatWindow.style.display = 'none';
        chatBtn.style.display = 'flex';
        if(chatCallout) chatCallout.style.display = 'block';
    }
}

function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }

function addMessage(text, sender) {
    const chatBody = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
    div.innerHTML = text;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    
    // Hiệu ứng đang gõ
    const chatBody = document.getElementById('chat-messages');
    const loader = document.createElement('div');
    loader.id = 'bot-loading';
    loader.className = 'message bot-message';
    loader.innerHTML = '...';
    chatBody.appendChild(loader);
    chatBody.scrollTop = chatBody.scrollHeight;

    // Gọi API
    // Lưu ý: SITE_URL đã được định nghĩa ở footer.php
    fetch(SITE_URL + '/ajax/chat_gemini.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        if(document.getElementById('bot-loading')) document.getElementById('bot-loading').remove();
        if (data.reply) { addMessage(data.reply, 'bot'); }
        else { addMessage('Lỗi: Không nhận được phản hồi.', 'bot'); }
    })
    .catch(error => {
        if(document.getElementById('bot-loading')) document.getElementById('bot-loading').remove();
        addMessage('Lỗi kết nối mạng!', 'bot');
        console.error(error);
    });
}
// --- LOGIC HỘP QUÀ (LUCKY GIFT) ---
let giftModalInstance = null;

function openGift() {
    const modalEl = document.getElementById('luckyFoodModal');
    if (!modalEl) return; // Tránh lỗi nếu không tìm thấy modal

    // Di chuyển modal ra ngoài cùng body để tránh bị che
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }

    // Khởi tạo Bootstrap Modal nếu chưa có
    if (!giftModalInstance) {
        giftModalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static', // Không tắt khi click ra ngoài
            keyboard: false     // Không tắt khi bấm ESC
        });
    }

    giftModalInstance.show();

    // Reset trạng thái (Hiện hộp quà lắc)
    const giftAnim = document.getElementById('gift-animation-container');
    const foodResult = document.getElementById('food-result-container');
    
    if(giftAnim) giftAnim.style.display = 'block';
    if(foodResult) foodResult.style.display = 'none';

    // Gọi API lấy món ăn (Giả lập delay 1.5s để hồi hộp)
    setTimeout(() => {
        // Sử dụng biến SITE_URL toàn cục (được định nghĩa ở footer)
        const apiUrl = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/get_random_recipe.php';

        fetch(apiUrl)
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const food = res.data;
                    
                    // Điền dữ liệu vào modal
                    if(document.getElementById('time-greeting')) 
                        document.getElementById('time-greeting').textContent = res.message;
                    if(document.getElementById('food-title'))
                        document.getElementById('food-title').textContent = food.title;
                    if(document.getElementById('food-cat'))
                        document.getElementById('food-cat').textContent = food.category_name;
                    
                    const imgEl = document.getElementById('food-img');
                    if(imgEl) {
                        const basePath = (typeof SITE_URL !== 'undefined' ? SITE_URL : '');
                        imgEl.src = basePath + '/' + food.thumbnail;
                    }
                    
                    const linkEl = document.getElementById('food-link');
                    if(linkEl) {
                        const basePath = (typeof SITE_URL !== 'undefined' ? SITE_URL : '');
                        linkEl.href = basePath + '/post/' + food.slug;
                    }

                    // Chuyển cảnh: Ẩn quà -> Hiện món ăn
                    if(giftAnim) giftAnim.style.display = 'none';
                    if(foodResult) {
                        foodResult.style.display = 'block';
                        foodResult.classList.remove('animate__zoomIn');
                        void foodResult.offsetWidth; // Trigger reflow
                        foodResult.classList.add('animate__animated', 'animate__zoomIn');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi kết nối!');
                if(giftModalInstance) giftModalInstance.hide();
            });
    }, 1500);
}

// Gán sự kiện click cho nút mở quà
document.addEventListener('DOMContentLoaded', function() {
    const newGiftBtn = document.getElementById('btn-open-surprise');
    if(newGiftBtn) {
        newGiftBtn.addEventListener('click', openGift);
    }
});