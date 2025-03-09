<?php
// Start session
session_start();

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

// Include database connection
require_once 'assets/db_connection.php';

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";
$registration_success = false;

// Check if user just registered
if(isset($_GET['registered']) && $_GET['registered'] == 'true') {
    $registration_success = true;
}

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, email, username, password FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $email, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            
                            // Redirect user to profile page
                            header("location: profile.php");
                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
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
    <title>GYMVERSE - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="lietotaja-view.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            background: url('images/gym-background.jpg') no-repeat center center;
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
        
        .login-form-container {
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
        
        .login-form-container::before {
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
            margin-bottom: 40px;
            color: var(--light);
            font-size: 3rem;
            letter-spacing: 4px;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.7);
            font-family: 'Koulen', sans-serif;
        }
        
        .login-form h2 {
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
        
        .login-btn {
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
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
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
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .remember-me input {
            margin-right: 10px;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            color: var(--primary);
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
            margin-bottom: 30px;
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
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            text-align: center;
            animation: alertFadeIn 0.5s ease-in-out;
        }
        
        .alert-danger {
            background-color: #ff3333;
        }
        
        .alert-success {
            background-color: #00cc66;
        }
        
        @keyframes alertFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
    <div class="login-container">
        <div class="login-form-container">
            <div class="login-logo">GYMVERSE</div>
            
            <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>LOGIN TO YOUR ACCOUNT</h2>
                
                <?php 
                if($registration_success) {
                    echo '<div class="alert alert-success">Registration successful! Please log in with your credentials.</div>';
                }
                
                if(!empty($login_err)) {
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>
                
                <div class="form-group">
                    <i class="form-icon fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" placeholder="Email Address" value="<?php echo $email; ?>">
                    <?php if(!empty($email_err)) echo '<span class="invalid-feedback">' . $email_err . '</span>'; ?>
                </div>
                
                <div class="form-group">
                    <i class="form-icon fas fa-lock"></i>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Password">
                    <?php if(!empty($password_err)) echo '<span class="invalid-feedback">' . $password_err . '</span>'; ?>
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember-me" name="remember-me">
                    <label for="remember-me">Remember me</label>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <span id="login-text">LOGIN</span>
                    <span id="login-loading" style="display: none;"><div class="loading-dots"></div></span>
                </button>
                
                <div class="form-divider">OR</div>
                
                <div class="social-login">
                    <button type="button" class="social-btn"><i class="fab fa-google"></i></button>
                    <button type="button" class="social-btn"><i class="fab fa-facebook-f"></i></button>
                    <button type="button" class="social-btn"><i class="fab fa-apple"></i></button>
                </div>
                
                <div class="login-footer">
                    Don't have an account? <a href="register.php">Register Now</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Home Button -->
    <a href="index.php" class="home-button" style="position: fixed; top: 20px; left: 20px; background-color: rgba(25, 25, 25, 0.8); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 1000; transition: all 0.3s ease;">
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