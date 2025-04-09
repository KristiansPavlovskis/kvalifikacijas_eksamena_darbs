<?php
session_start();

if(isset($_SESSION['user_id'])) {
    if(isset($_SESSION['redirect_url'])) {
        $redirect = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: $redirect");
    } else {
        header("Location: profile/profile.php");
    }
    exit;
}

if(isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

require_once '../assets/db_connection.php';

$email = $password = "";
$email_err = $password_err = $login_err = "";
$registration_success = false;

if(isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $registration_success = true;
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        $redirect = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
        header("location: $redirect");
    } else {
        header("location: profile/profile.php");
    }
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, username, email, password FROM users WHERE email = ?";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_assoc($result);
                    
                    if(password_verify($password, $row["password"])) {
                        session_start();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $row["id"];
                        $_SESSION["username"] = $row["username"];
                        $_SESSION["email"] = $row["email"];
                        
                        $user_id = $row["id"];
                        $sql = "SELECT r.name FROM roles r 
                                JOIN user_roles ur ON r.id = ur.role_id 
                                WHERE ur.user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user_roles = [];
                        while ($role = $result->fetch_assoc()) {
                            $user_roles[] = $role['name'];
                        }
                        $_SESSION["user_roles"] = $user_roles;
                        
                        $is_admin = in_array('administrator', $user_roles) || in_array('super_admin', $user_roles);
                        if (isset($_GET['redirect']) && strpos($_GET['redirect'], '/admin/') !== false && !$is_admin) {
                            header("location: /pages/index.php");
                        } else {
                            if(!empty($redirect)) {
                                header("location: " . $redirect);
                            } else {
                                header("location: profile/profile.php");
                            }
                        }
                        exit;
                    } else {
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
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
    <title>Login | GYMVERSE</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            background: url('../assets/images/gym-background2.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 10, 0.8);
            z-index: 1;
        }
        
        .login-box {
            width: 100%;
            max-width: 450px;
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
        
        .login-box::before {
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
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-size: 3rem;
            letter-spacing: 4px;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.7);
            font-family: 'Koulen', sans-serif;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--light);
            margin: 0 0 10px 0;
            font-size: 28px;
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #2ecc71;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff4d4d;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 16px;
            color: var(--primary);
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
            box-sizing: border-box;
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
        
        .form-control.is-invalid {
            border-color: #ff4d4d;
        }
        
        .invalid-feedback {
            color: #ff4d4d;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            cursor: pointer;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            margin-left: auto;
        }
        
        .forgot-password:hover {
            color: #ff3333;
            text-decoration: underline;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            padding: 15px 30px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-family: 'Koulen', sans-serif;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            text-transform: uppercase;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .login-footer a:hover {
            color: #ff3333;
            text-decoration: underline;
        }
        
        .login-form {
            margin-top: 30px;
        }
        
        .form-group:last-of-type {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0 20px;
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
        
        /* Home Button */
        .home-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: rgba(25, 25, 25, 0.8);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .home-button:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">GYMVERSE</div>
            
            <div class="login-header">
                <h1>WELCOME BACK</h1>
                <p>Sign in to continue your fitness journey</p>
            </div>
            
            <?php if($registration_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! Please login.
            </div>
            <?php endif; ?>
            
            <?php if(!empty($login_err)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $login_err; ?>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" placeholder="Enter your email">
                    </div>
                    <?php if(!empty($email_err)): ?>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password">
                    </div>
                    <?php if(!empty($password_err)): ?>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                    <span id="login-text">SIGN IN</span>
                    <span id="login-loading" style="display: none;"><div class="loading-dots"></div></span>
                </button>
            </form>
            
            <div class="form-divider">OR</div>
            
            <div class="social-login">
                <button type="button" class="social-btn"><i class="fab fa-google"></i></button>
                <button type="button" class="social-btn"><i class="fab fa-facebook-f"></i></button>
                <button type="button" class="social-btn"><i class="fab fa-apple"></i></button>
            </div>
            
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Sign up</a></p>
            </div>
        </div>
    </div>
    
    <!-- Home Button -->
    <a href="index.php" class="home-button">
        <i class="fas fa-home" style="font-size: 20px;"></i>
    </a>
    
    <script>
        // Show loading animation when form is submitted
        document.querySelector('.login-form').addEventListener('submit', function() {
            document.getElementById('login-text').style.display = 'none';
            document.getElementById('login-loading').style.display = 'block';
        });
    </script>
</body>
</html> 
<?php include '../includes/footer.php'; ?>