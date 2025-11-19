</main>
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Master Chef</h5>
                    <p>Nơi chia sẻ công thức nấu ăn, kỹ thuật nấu nướng và đam mê ẩm thực.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Liên kết nhanh</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-white">Trang chủ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/type/cong-thuc" class="text-white">Công thức</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/type/meo" class="text-white">Mẹo nấu ăn</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/type/blog" class="text-white">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Liên hệ</h5>
                    <address>
                        <p><i class="fas fa-envelope me-2"></i> contact@masterchef.com</p>
                        <p><i class="fas fa-phone me-2"></i> +84 123 456 789</p>
                        <p><i class="fas fa-map-marker-alt me-2"></i> Hà Nội, Việt Nam</p>
                    </address>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Master Chef. All rights reserved.</p>
                </div>
            </div>
        </div>
        
    </footer>
    
    <div id="chat-widget-btn" onclick="toggleChat()" title="Chat với Trợ lý ảo">
        <i class="fas fa-robot"></i>
    </div>

    <div id="chat-widget-window">
        <div class="chat-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-hat-chef me-2"></i>
                <span>Trợ lý Master Chef</span>
            </div>
            <i class="fas fa-times close-chat" onclick="toggleChat()"></i>
        </div>
        
        <div class="chat-body" id="chat-messages">
            <div class="message bot-message">
                Xin chào! Mình là trợ lý AI của Master Chef 🍳. Bạn muốn hỏi công thức hay mẹo nấu ăn gì hôm nay?
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chat-input" placeholder="Hỏi gì đó..." onkeypress="handleEnter(event)">
            <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <style>
        /* Nút Chat Nổi - Vị trí dễ thấy nhất */
        #chat-widget-btn {
            position: fixed;
            bottom: 30px;      /* Cách đáy 30px */
            right: 30px;       /* Cách phải 30px */
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 10000;    /* Luôn nổi trên cùng */
            transition: all 0.3s;
            animation: pulse 2s infinite;
        }
        
        #chat-widget-btn:hover { transform: scale(1.1); }

        /* Hiệu ứng sóng lan tỏa để thu hút chú ý */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(33, 147, 176, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(33, 147, 176, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 147, 176, 0); }
        }

        /* Cửa sổ Chat */
        #chat-widget-window {
            position: fixed;
            bottom: 100px;     /* Hiện ngay trên nút chat */
            right: 30px;
            width: 350px;
            height: 450px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 40px rgba(0,0,0,0.2);
            z-index: 10001;    /* Cao hơn nút chat */
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #eee;
        }

        /* Header xanh */
        .chat-header {
            background: linear-gradient(135deg, #2193b0, #6dd5ed);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .close-chat { cursor: pointer; opacity: 0.8; }
        .close-chat:hover { opacity: 1; }

        /* Nội dung chat */
        .chat-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        /* Bong bóng tin nhắn */
        .message {
            max-width: 85%;
            padding: 10px 15px;
            border-radius: 15px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .bot-message {
            background: #fff;
            color: #333;
            border: 1px solid #eee;
            border-bottom-left-radius: 2px;
            align-self: flex-start;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .user-message {
            background: #2193b0;
            color: white;
            border-bottom-right-radius: 2px;
            margin-left: auto;
            box-shadow: 0 2px 5px rgba(33, 147, 176, 0.3);
        }

        /* Footer nhập liệu */
        .chat-footer {
            padding: 10px;
            background: #fff;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        #chat-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            outline: none;
            transition: border-color 0.3s;
        }
        #chat-input:focus { border-color: #2193b0; }

        .chat-footer button {
            background: #2193b0;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }
        .chat-footer button:hover { background: #1a7a94; }

        /* Hiệu ứng loading */
        .typing-indicator {
            display: none;
            padding: 10px;
            background: #fff;
            border-radius: 15px;
            width: fit-content;
            margin-bottom: 10px;
            border: 1px solid #eee;
        }
        .typing-indicator span {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #aaa;
            border-radius: 50%;
            animation: typing 1.4s infinite both;
            margin: 0 2px;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 100% { opacity: 0.2; } 50% { opacity: 1; } }
        
        /* Ẩn nút Back To Top nếu nó che mất nút Chat */
        #backToTop {
            bottom: 100px !important; /* Đẩy nút lên trên nút chat */
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }
    </style>

    <script>
        function toggleChat() {
            const chatWindow = document.getElementById('chat-widget-window');
            const chatBtn = document.getElementById('chat-widget-btn');
            
            if (chatWindow.style.display === 'none' || chatWindow.style.display === '') {
                chatWindow.style.display = 'flex';
                chatBtn.style.display = 'none'; // Ẩn nút tròn khi mở chat
                setTimeout(() => document.getElementById('chat-input').focus(), 100);
            } else {
                chatWindow.style.display = 'none';
                chatBtn.style.display = 'flex'; // Hiện lại nút tròn
            }
        }

        function handleEnter(e) {
            if (e.key === 'Enter') sendMessage();
        }

        function addMessage(text, sender) {
            const chatBody = document.getElementById('chat-messages');
            const div = document.createElement('div');
            div.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
            div.innerHTML = text; 
            chatBody.appendChild(div);
            chatBody.scrollTop = chatBody.scrollHeight; 
        }

        function showLoading() {
            const chatBody = document.getElementById('chat-messages');
            const loader = document.createElement('div');
            loader.id = 'bot-loading';
            loader.classList.add('typing-indicator');
            loader.style.display = 'block';
            loader.innerHTML = '<span></span><span></span><span></span>';
            chatBody.appendChild(loader);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function removeLoading() {
            const loader = document.getElementById('bot-loading');
            if (loader) loader.remove();
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (!message) return;

            addMessage(message, 'user');
            input.value = '';
            showLoading();

            fetch('<?php echo SITE_URL; ?>/ajax/chat_gemini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                removeLoading();
                if (data.reply) {
                    addMessage(data.reply, 'bot');
                } else {
                    addMessage('Xin lỗi, đầu bếp đang bận xíu. Bạn hỏi lại sau nhé!', 'bot');
                }
            })
            .catch(error => {
                removeLoading();
                addMessage('Lỗi kết nối mạng!', 'bot');
                console.error('Error:', error);
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>