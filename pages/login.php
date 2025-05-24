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
                        
                        header("location: ../profile/profile.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GYMVERSE</title>
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
        
        .login-container {
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
        
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
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

        .loading-dots {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: white;
            animation: bounce 1.4s infinite ease-in-out both;
        }
        
        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
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
            .login-container {
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
    <div class="login-container">
        <div class="form-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="logo-text">GYMVERSE</div>
            </div>
            
            <div class="form-header">
                <h1>Sign In to GYMVERSE</h1>
                <p>Start your journey</p>
            </div>
            
            <?php if($registration_success): ?>
            <div class="alert alert-success">
                Registration successful! Please login with your credentials.
            </div>
            <?php endif; ?>
            
            <?php if(!empty($login_err)): ?>
            <div class="alert alert-danger">
                <?php echo $login_err; ?>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-control">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" 
                        class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                        value="<?php echo $email; ?>" placeholder="example@email.com">
                    <?php if(!empty($email_err)): ?>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-control">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" 
                        class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                        placeholder="••••••••">
                    <?php if(!empty($password_err)): ?>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                    <span id="login-text">Sign In</span>
                    <span id="login-loading" style="display: none;">
                        <div class="loading-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </span>
                </button>                

                <div class="account-link">
                    <p>Don't have an account? <a href="register.php">Sign up</a></p>
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
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('login-text').style.display = 'none';
            document.getElementById('login-loading').style.display = 'block';
        });
    </script>
</body>
</html> 