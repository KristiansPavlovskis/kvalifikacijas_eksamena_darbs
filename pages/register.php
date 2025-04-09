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
                }
                
                header("location: login.php?registered=true");
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
    <?php include '../includes/header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/lietotaja-view.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            background: url('images/gym-background2.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 10, 0.8);
            z-index: 1;
        }
        
        .register-form-container {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            margin: auto;
            background: rgba(25, 25, 25, 0.9);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.5);
            z-index: 2;
            position: relative;
            overflow: hidden;
            animation: formFadeIn 0.8s ease-in-out;
        }
        
        @keyframes formFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-form-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, transparent, var(--primary), transparent, transparent);
            transform: rotate(45deg);
            animation: borderLight 3s linear forwards;
            z-index: -1;
        }
        
        @keyframes borderLight {
            0% { top: -50%; left: -50%; }
            100% { top: 50%; left: 50%; }
        }
        
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-size: 3rem;
            letter-spacing: 4px;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.7);
            font-family: 'Koulen', sans-serif;
        }
        
        .register-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 16px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-control:focus {
            box-shadow: 0 0 10px var(--primary);
            background: rgba(255, 255, 255, 0.2);
            border-color: var(--primary);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 16px;
            color: var(--primary);
        }
        
        .register-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: var(--primary);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            font-family: 'Koulen', sans-serif;
        }
        
        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }
        
        .register-btn:hover::before {
            left: 100%;
        }
        
        .register-btn:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .register-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .register-footer a:hover {
            color: #ff3333;
            text-decoration: underline;
        }
        
        .terms {
            margin: 20px 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            text-align: center;
        }
        
        .terms a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .terms a:hover {
            color: #ff3333;
            text-decoration: underline;
        }
        
        .form-divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-divider::before, .form-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-divider::before {
            margin-right: 10px;
        }
        
        .form-divider::after {
            margin-left: 10px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            font-size: 18px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            background: var(--primary);
        }
        
        .invalid-feedback {
            color: #ff4d4d;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 10px;
            border-radius: 5px;
            background: #444;
            position: relative;
            transition: all 0.3s;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            border-radius: 5px;
            transition: all 0.3s;
            width: 0%;
        }
        
        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
        }
        
        .weak {
            background: #ff4d4d;
            width: 25%;
        }
        
        .medium {
            background: #ffa700;
            width: 50%;
        }
        
        .strong {
            background: #ffff00;
            width: 75%;
        }
        
        .very-strong {
            background: #00ff00;
            width: 100%;
        }
        
        .loading-dots {
            position: relative;
            width: 10px;
            height: 10px;
            border-radius: 5px;
            background-color: white;
            color: white;
            animation: dotPulse 1.5s infinite linear;
            animation-delay: 0.25s;
        }
        
        .loading-dots::before,
        .loading-dots::after {
            content: '';
            display: inline-block;
            position: absolute;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 5px;
            background-color: white;
            color: white;
            animation: dotPulse 1.5s infinite linear;
        }
        
        .loading-dots::before {
            left: -15px;
            animation-delay: 0s;
        }
        
        .loading-dots::after {
            left: 15px;
            animation-delay: 0.5s;
        }
        
        @keyframes dotPulse {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.3); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form-container">
            <div class="register-logo">GYMVERSE</div>
            
            <form class="register-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>CREATE YOUR ACCOUNT</h2>
                
                <div class="form-group">
                    <i class="form-icon fas fa-user"></i>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" placeholder="Username" value="<?php echo $username; ?>">
                    <?php if(!empty($username_err)) echo '<span class="invalid-feedback">' . $username_err . '</span>'; ?>
                </div>
                
                <div class="form-group">
                    <i class="form-icon fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" placeholder="Email Address" value="<?php echo $email; ?>">
                    <?php if(!empty($email_err)) echo '<span class="invalid-feedback">' . $email_err . '</span>'; ?>
                </div>
                
                <div class="form-group">
                    <i class="form-icon fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Password">
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-strength-meter"></div>
                    </div>
                    <div class="password-strength-text" id="password-strength-text"></div>
                    <?php if(!empty($password_err)) echo '<span class="invalid-feedback">' . $password_err . '</span>'; ?>
                </div>
                
                <div class="form-group">
                    <i class="form-icon fas fa-lock"></i>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" placeholder="Confirm Password">
                    <?php if(!empty($confirm_password_err)) echo '<span class="invalid-feedback">' . $confirm_password_err . '</span>'; ?>
                </div>
                
                <div class="terms">
                    By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </div>
                
                <button type="submit" class="register-btn">
                    <span id="register-text">CREATE ACCOUNT</span>
                    <span id="register-loading" style="display: none;"><div class="loading-dots"></div></span>
                </button>
                
                <div class="form-divider">OR</div>
                
                <div class="social-login">
                    <button type="button" class="social-btn"><i class="fab fa-google"></i></button>
                    <button type="button" class="social-btn"><i class="fab fa-facebook-f"></i></button>
                    <button type="button" class="social-btn"><i class="fab fa-apple"></i></button>
                </div>
                
                <div class="register-footer">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Home Button -->
    <a href="index.php" class="home-button" style="position: fixed; top: 20px; left: 20px; background-color: rgba(25, 25, 25, 0.8); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 1000; transition: all 0.3s ease;">
        <i class="fas fa-home" style="font-size: 20px;"></i>
    </a>
    
    <script>
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('password-strength-meter');
            const text = document.getElementById('password-strength-text');
            
            // Reset
            meter.className = 'password-strength-meter';
            text.textContent = '';
            
            if (password === '') {
                return;
            }
            
            // Calculate strength
            let strength = 0;
            
            // Length
            if (password.length >= 8) {
                strength += 1;
            }
            
            // Lowercase letters
            if (password.match(/[a-z]/)) {
                strength += 1;
            }
            
            // Uppercase letters
            if (password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            // Numbers
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
            // Special characters
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 1;
            }
            
            // Update UI
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
        
        // Show loading animation when form is submitted
        document.querySelector('.register-form').addEventListener('submit', function() {
            document.getElementById('register-text').style.display = 'none';
            document.getElementById('register-loading').style.display = 'block';
        });
    </script>
</body>
</html> 
<?php include '../includes/footer.php'; ?>