<?php
// Database connection parameters for server connection
$host = 'localhost';
$username = 'root';
$password = '';

// Initialize variables for success/error messages
$success_message = '';
$error_message = '';
$db_created = false;
$table_created = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connect to MySQL (no database specified)
        $conn = new PDO("mysql:host=$host", $username, $password);
        
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS gymverse_db";
        $conn->exec($sql);
        $db_created = true;
        
        // Connect to the gymverse_db database
        $conn = new PDO("mysql:host=$host;dbname=gymverse_db", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create users table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        $table_created = true;
        
        $success_message = "Database setup completed successfully!";
        
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
    
    // Close connection
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYMVERSE - Database Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Koulen&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4d4d;
            --secondary: #333;
            --dark: #0A0A0A;
            --light: #f5f5f5;
            --success: #00cc66;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: var(--dark);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .setup-container {
            width: 100%;
            max-width: 600px;
            background: rgba(25, 25, 25, 0.9);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.5);
            padding: 40px;
            position: relative;
        }
        
        .setup-logo {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-size: 2.5rem;
            letter-spacing: 4px;
            font-weight: bold;
            text-shadow: 0 0 15px rgba(255, 77, 77, 0.7);
            font-family: 'Koulen', sans-serif;
        }
        
        .setup-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--light);
            font-family: 'Koulen', sans-serif;
            letter-spacing: 2px;
            font-size: 1.5rem;
        }
        
        .setup-content {
            margin-bottom: 30px;
        }
        
        .setup-content p {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .setup-btn {
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
        
        .setup-btn:hover {
            background: #ff3333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 77, 77, 0.3);
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: white;
            text-align: center;
        }
        
        .alert-success {
            background-color: var(--success);
        }
        
        .alert-danger {
            background-color: var(--primary);
        }
        
        .setup-steps {
            margin: 30px 0;
        }
        
        .setup-step {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            font-weight: bold;
        }
        
        .step-text {
            flex: 1;
        }
        
        .step-status {
            margin-left: 15px;
        }
        
        .step-success {
            color: var(--success);
        }
        
        .setup-footer {
            text-align: center;
            margin-top: 30px;
        }
        
        .setup-footer a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            padding: 10px 20px;
            border: 1px solid var(--primary);
            border-radius: 5px;
            margin: 0 10px;
            display: inline-block;
        }
        
        .setup-footer a:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .setup-footer a.primary {
            background-color: var(--primary);
            color: white;
        }
        
        .setup-footer a.primary:hover {
            background-color: #ff3333;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-logo">GYMVERSE</div>
        <h1 class="setup-title">DATABASE SETUP</h1>
        
        <div class="setup-content">
            <p>This page will help you set up the necessary database for the GYMVERSE application. The setup process will:</p>
            
            <div class="setup-steps">
                <div class="setup-step">
                    <div class="step-number">1</div>
                    <div class="step-text">Create the <strong>gymverse_db</strong> database if it doesn't exist</div>
                    <div class="step-status">
                        <?php if($db_created): ?>
                            <i class="fas fa-check-circle step-success"></i>
                        <?php else: ?>
                            <i class="fas fa-circle"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="setup-step">
                    <div class="step-number">2</div>
                    <div class="step-text">Create the <strong>users</strong> table for authentication</div>
                    <div class="step-status">
                        <?php if($table_created): ?>
                            <i class="fas fa-check-circle step-success"></i>
                        <?php else: ?>
                            <i class="fas fa-circle"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(empty($success_message) && empty($error_message)): ?>
                <p>Click the button below to start the setup process:</p>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <button type="submit" class="setup-btn">
                        <i class="fas fa-database"></i> Setup Database
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="setup-footer">
            <?php if(!empty($success_message)): ?>
                <a href="../index.php" class="primary">Go to Homepage</a>
                <a href="../register.php">Register Account</a>
            <?php else: ?>
                <a href="../index.php">Back to Homepage</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 