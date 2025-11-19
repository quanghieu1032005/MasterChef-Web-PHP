</div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        // Toggle Sidebar
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("menu-toggle");

        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };

        // Khởi tạo Summernote (nếu có)
        $(document).ready(function() {
            if($('.summernote').length > 0) {
                $('.summernote').summernote({
                    placeholder: 'Nhập nội dung...',
                    tabsize: 2,
                    height: 300
                });
            }
            
            // Ẩn alert sau 3s
            setTimeout(function() {
                $(".alert").alert('close');
            }, 3000);
        });
    </script>
</body>
</html>