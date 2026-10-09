
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đăng ký tài khoản - Quản lý nhà trọ</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f1f5f9;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }

            .register-box {
                width: 100%;
                max-width: 450px;
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            }

            h2 {
                text-align: center;
                color: #2563eb;
                margin-top: 0;
            }

            .description {
                text-align: center;
                color: #64748b;
                margin-bottom: 25px;
            }

            .form-group {
                margin-bottom: 17px;
            }

            label {
                display: block;
                font-weight: bold;
                margin-bottom: 7px;
                color: #334155;
            }

            input {
                width: 100%;
                padding: 12px;
                border: 1px solid #cbd5e1;
                border-radius: 7px;
                font-size: 15px;
            }

            input:focus {
                outline: none;
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
            }

            input.invalid {
                border-color: #dc2626;
            }

            .error {
                color: #dc2626;
                font-size: 13px;
                margin-top: 5px;
            }

            button {
                width: 100%;
                padding: 13px;
                border: none;
                border-radius: 7px;
                background: #2563eb;
                color: white;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
            }

            button:hover {
                background: #1d4ed8;
            }

            button:disabled {
                opacity: 0.6;
                cursor: wait;
            }

            .message {
                margin-top: 15px;
                text-align: center;
                font-size: 14px;
            }

            .login-link {
                text-align: center;
                margin-top: 20px;
            }

            .login-link a {
                color: #2563eb;
                text-decoration: none;
            }
        </style>
    </head>

    <body>
    <div class="register-box">
        <h2>ĐĂNG KÝ TÀI KHOẢN</h2>

        <p class="description">
            Hệ thống quản lý nhà trọ
        </p>

        <form id="registerForm" novalidate>

            <div class="form-group">
                <label for="username">Tên đăng nhập *</label>
                <input type="text" id="username"
                    name="username" autocomplete="username"
                    placeholder="Nhập tên đăng nhập">
                <div class="error" id="error_username"></div>
            </div>

            <div class="form-group">
                <label for="full_name">Họ và tên *</label>
                <input type="text" id="full_name"
                    name="full_name" autocomplete="name"
                    placeholder="Nhập họ và tên">
                <div class="error" id="error_full_name"></div>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu *</label>
                <input type="password" id="password"
                    name="password" autocomplete="new-password"
                    placeholder="Ít nhất 6 ký tự">
                <div class="error" id="error_password"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu *</label>
                <input type="password" id="confirm_password"
                    name="confirm_password" autocomplete="new-password"
                    placeholder="Nhập lại mật khẩu">
                <div class="error" id="error_confirm_password"></div>
            </div>

            <div class="form-group">
                <label for="phone">Số điện thoại *</label>
                <input type="tel" id="phone"
                    name="phone" autocomplete="tel"
                    placeholder="Nhập số điện thoại">
                <div class="error" id="error_phone"></div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email"
                    name="email" autocomplete="email"
                    placeholder="Nhập địa chỉ email">
                <div class="error" id="error_email"></div>
            </div>

            <button type="submit" id="submitBtn">
                ĐĂNG KÝ
            </button>

            <div class="message" id="message" role="status" aria-live="polite"></div>
        </form>

        <div class="login-link">
            Đã có tài khoản?
            <a href="login.php">Đăng nhập</a>
        </div>
    </div>

    <script>
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const message = document.getElementById('message');

    const fields = [
        'username',
        'full_name',
        'password',
        'confirm_password',
        'phone',
        'email'
    ];

    // Hiển thị lỗi tại từng trường
    function showError(id, text) {
        const errorElement = document.getElementById('error_' + id);
        const inputElement = document.getElementById(id);

        if (errorElement) {
            errorElement.textContent = text;
        }

        if (inputElement) {
            inputElement.classList.toggle('invalid', !!text);
        }
    }

    // Xóa tất cả thông báo lỗi
    function clearErrors() {
        fields.forEach(id => showError(id, ''));
        message.textContent = '';
    }

    // Xóa lỗi khi người dùng nhập lại
    fields.forEach(id => {
        const input = document.getElementById(id);

        input.addEventListener('input', function () {
            showError(id, '');
            message.textContent = '';
        });
    });

    // Xử lý đăng ký
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();

        // 1. Kiểm tra các trường bắt buộc
        for (const id of fields) {
            const input = document.getElementById(id);

            if (!input.value.trim()) {
                showError(id, 'Vui lòng nhập thông tin này.');
                input.focus();
                return;
            }
        }

        // 2. Kiểm tra email
        const emailInput = document.getElementById('email');

        if (!emailInput.validity.valid) {
            showError('email', 'Vui lòng nhập email hợp lệ.');
            emailInput.focus();
            return;
        }

        // 3. Kiểm tra tên đăng nhập
        const username = document.getElementById('username').value.trim();

        if (username.length > 50) {
            showError('username', 'Tên đăng nhập không được quá 50 ký tự.');
            document.getElementById('username').focus();
            return;
        }

        // 4. Kiểm tra mật khẩu
        const password = document.getElementById('password').value;
        const confirmPassword =
            document.getElementById('confirm_password').value;

        if (password.length < 8) {
            showError('password', 'Mật khẩu phải có ít nhất 8 ký tự.');
            document.getElementById('password').focus();
            return;
        }

        if (password.length > 72) {
            showError('password', 'Mật khẩu không được quá 72 ký tự.');
            document.getElementById('password').focus();
            return;
        }

        // 5. Xác nhận mật khẩu
        if (password !== confirmPassword) {
            showError(
                'confirm_password',
                'Mật khẩu xác nhận không khớp.'
            );
            document.getElementById('confirm_password').focus();
            return;
        }

        // 6. Chuẩn bị dữ liệu gửi PHP qua POST
        const formData = new FormData();

        fields.forEach(id => {
            const input = document.getElementById(id);
            formData.append(id, input.value.trim());
        });

        // Giữ nguyên mật khẩu, không trim để tránh thay đổi mật khẩu người dùng nhập
        formData.set(
            'password',
            document.getElementById('password').value
        );

        formData.set(
            'confirm_password',
            document.getElementById('confirm_password').value
        );

        // 7. Gửi dữ liệu đến PHP
        submitBtn.disabled = true;
        submitBtn.textContent = 'ĐANG ĐĂNG KÝ...';

        try {
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.text();

            if (!response.ok) {
                throw new Error(
                    result || 'Không thể đăng ký tài khoản.'
                );
            }

            // PHP hiện tại trả về văn bản thường
            if (result.trim() === 'Registration successful.') {
                message.style.color = '#15803d';
                message.textContent =
                    'Đăng ký thành công! Bạn có thể đăng nhập.';
                form.reset();
            } else {
                message.style.color = '#dc2626';
                message.textContent =
                    result || 'Đăng ký chưa thành công.';
            }

        } catch (error) {
            console.error('Lỗi đăng ký:', error);

            message.style.color = '#dc2626';
            message.textContent =
                'Có lỗi xảy ra: ' + error.message;

        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'ĐĂNG KÝ';
        }
    });
    </script>
    <?php
    require_once __DIR__ . "/db.php";

    if($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit("Method not allowed");
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ){
        exit("Please fill in all fields.");
    }

    if(strlen($username) > 50) {
        exit("Username must not exceed 50 characters.");
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email format.");
    }

    if($password !== $confirm_password) {
        exit("Passwords do not match.");
    }

    if(strlen($password) < 8) {
        exit("Mật khẩu phải có ít nhất 8 ký tự.");
    }

    $sql = "SELECT id FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":email" => $email
    ]);

    if ($stmt->fetch()) {
        exit("Email already exists.");
    }

    // 7. Băm mật khẩu
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // 8. Thêm tài khoản vào database
    $sql = "INSERT INTO users (username, email, password)
            VALUES (:username, :email, :password)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":username" => $username,
        ":email" => $email,
        ":password" => $hashedPassword
    ]);

    http_response_code(201);
    echo "Registration successful.";
    ?>
    </body>
    </html>