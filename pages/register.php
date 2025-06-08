<?php
session_start();

if(isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

require_once '../assets/db_connection.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) > 0) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_password);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); 
            
            if(mysqli_stmt_execute($stmt)) {
                $new_user_id = mysqli_insert_id($conn);
                
                $role_sql = "INSERT INTO user_roles (user_id, role_id) VALUES (?, 10)";
                if($role_stmt = mysqli_prepare($conn, $role_sql)) {
                    mysqli_stmt_bind_param($role_stmt, "i", $new_user_id);
                    mysqli_stmt_execute($role_stmt);
                    mysqli_stmt_close($role_stmt);
                    
                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $new_user_id;
                    $_SESSION["username"] = $username;
                    $_SESSION["email"] = $email;
                    
                    $roles_sql = "SELECT r.name FROM roles r 
                                JOIN user_roles ur ON r.id = ur.role_id 
                                WHERE ur.user_id = ?";
                    if($roles_stmt = $conn->prepare($roles_sql)) {
                        $roles_stmt->bind_param("i", $new_user_id);
                        $roles_stmt->execute();
                        $roles_result = $roles_stmt->get_result();
                        $user_roles = [];
                        while ($role = $roles_result->fetch_assoc()) {
                            $user_roles[] = $role['name'];
                        }
                        $_SESSION["user_roles"] = $user_roles;
                        $roles_stmt->close();
                        
                        header("location: ../profile/profile.php");
                        exit;
                    }
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --light: #f5f5f5;
            --dark: #212529;
            --background: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .register-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }
        
        .form-container {
            width: 50%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        
        .image-container {
            width: 50%;
            background: url('../assets/images/papers.co-bj03-art-logo-wave-simple-minimal-dark-41-iphone-wallpaper.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            font-size: 30px;
            color: var(--primary);
            margin-right: 10px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .form-header {
            margin-bottom: 40px;
        }
        
        .form-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #6c757d;
            font-size: 16px;
        }
        
        .form-control {
            margin-bottom: 24px;
        }
        
        .form-control label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control input {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .form-control input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            padding: 14px 24px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            color: #fff;
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary:hover {
            background-color: #3b56d9;
            border-color: #3b56d9;
            box-shadow: 0 6px 10px rgba(67, 97, 238, 0.35);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .account-link {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin: 20px 0;
        }
        
        .account-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .account-link a:hover {
            text-decoration: underline;
        }
        
        .home-button {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            color: var(--dark);
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .home-button:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .password-strength {
            height: 4px;
            margin-top: 8px;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
        }
        
        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #6c757d;
            text-align: right;
        }
        
        .weak {
            background: #dc3545;
            width: 25%;
        }
        
        .medium {
            background: #ffc107;
            width: 50%;
        }
        
        .strong {
            background: #20c997;
            width: 75%;
        }
        
        .very-strong {
            background: #198754;
            width: 100%;
        }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        @media (max-width: 992px) {
            .form-container {
                width: 60%;
                padding: 40px;
            }
            
            .image-container {
                width: 40%;
            }
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column-reverse;
            }
            
            .form-container {
                width: 100%;
                height: 75vh;
            }
            
            .image-container {
                width: 100%;
                height: 25vh;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="form-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="logo-text">GYMVERSE</div>
            </div>
            
            <div class="form-header">
                <h1>Sign Up to GYMVERSE</h1>
                <p>Start your journey</p>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-control">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" 
                           class="<?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo $username; ?>" placeholder="Choose a username">
                    <?php if(!empty($username_err)) echo '<span class="invalid-feedback">' . $username_err . '</span>'; ?>
                </div>
                
                <div class="form-control">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" 
                           class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo $email; ?>" placeholder="example@email.com">
                    <?php if(!empty($email_err)) echo '<span class="invalid-feedback">' . $email_err . '</span>'; ?>
                </div>
                
                <div class="form-control">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" 
                           class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                           placeholder="Create a password">
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-strength-meter"></div>
                    </div>
                    <div class="password-strength-text" id="password-strength-text"></div>
                    <?php if(!empty($password_err)) echo '<span class="invalid-feedback">' . $password_err . '</span>'; ?>
                </div>
                
                <div class="form-control">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" 
                           class="<?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                           placeholder="Confirm your password">
                    <?php if(!empty($confirm_password_err)) echo '<span class="invalid-feedback">' . $confirm_password_err . '</span>'; ?>
                </div>
     
                <button type="submit" class="btn btn-primary btn-block" id="register-btn">
                    <span id="register-text">Create Account</span>
                    <span id="register-loading" style="display: none;">
                    </span>
                </button>
                
                <div class="account-link">
                    <p>Already have an account? <a href="login.php">Sign in</a></p>
                </div>
            </form>
        </div>
        
        <div class="image-container">
        </div>
    </div>
    
    <a href="landing.html" class="home-button">
        <i class="fas fa-home"></i>
    </a>
    
    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('password-strength-meter');
            const text = document.getElementById('password-strength-text');
            
            meter.className = 'password-strength-meter';
            text.textContent = '';
            
            if (password === '') {
                return;
            }
            
            let strength = 0;
            
            if (password.length >= 8) {
                strength += 1;
            }
            
            if (password.match(/[a-z]/)) {
                strength += 1;
            }
            
            if (password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 1;
            }
            
            switch (strength) {
                case 0:
                case 1:
                    meter.className += ' weak';
                    text.textContent = 'Weak';
                    break;
                case 2:
                    meter.className += ' medium';
                    text.textContent = 'Medium';
                    break;
                case 3:
                    meter.className += ' strong';
                    text.textContent = 'Strong';
                    break;
                case 4:
                case 5:
                    meter.className += ' very-strong';
                    text.textContent = 'Very Strong';
                    break;
            }
        });
        
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('register-text').style.display = 'none';
            document.getElementById('register-loading').style.display = 'block';
        });
    </script>
</body>
</html>