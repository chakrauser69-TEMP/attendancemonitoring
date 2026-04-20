<?php
session_start();
require 'connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $valid_roles = ['student', 'teacher'];
        if (!in_array($role, $valid_roles)) {
            $error = "Invalid role.";
        } else {
            $sql = "SELECT user_id, username, password, role FROM users 
                    WHERE username = :username AND password = :password AND role = :role";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':role',     $role,     PDO::PARAM_STR);
            
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

               
                $_SESSION['user_id']   = $user['user_id'];   
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                echo '<style>
                    .login-success {
                        position: fixed; top: 20px; right: 20px; z-index: 9999;
                        color: white; background-color: #9acd32; width: 500px; height: 30px;
                        border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                        padding: 20px; text-align: center; line-height: 1.5;
                        border: 1px solid #7cb518;
                        animation: successInOut 4s ease-in-out forwards;
                    }
                    @keyframes successInOut {
                        0% { transform: translateX(100%) scale(0.8); opacity: 0; }
                        15% { transform: translateX(0) scale(1); opacity: 1; }
                        85% { transform: translateX(0) scale(1); opacity: 1; }
                        100% { transform: translateX(100%) scale(0.8); opacity: 0; }
                    }
                </style>
                <div class="login-success">Welcome Back ' . htmlspecialchars($username) . ' 🎉</div>';

                
                if ($user['role'] === 'student') {
                    header("Location: student_dashboard.php");
                } else if ($user['role'] === 'teacher') {
                    header("Location: teacher_dashboard.php");
                }
                exit;
            } else {
                echo '<style>
                    .login-failed {
                        position: fixed; top: 20px; right: 20px; z-index: 9999;
                        color: white; background-color: red; width: 500px; height: 30px;
                        border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                        padding: 20px; text-align: center; line-height: 1.5;
                        border: 1px solid #7cb518;
                        animation: failedInOut 4s ease-in-out forwards;
                    }
                    @keyframes failedInOut {
                        0% { transform: translateX(100%) scale(0.8); opacity: 0; }
                        15% { transform: translateX(0) scale(1); opacity: 1; }
                        85% { transform: translateX(0) scale(1); opacity: 1; }
                        100% { transform: translateX(100%) scale(0.8); opacity: 0; }
                    }
                </style>
                <div class="login-failed">Invalid Credentials!</div>';
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="styles/style.css">
  <title>Student Attendance System</title>
</head>
<body>
  <section>
    <h1>Student Attendance Monitoring System</h1><hr>
    <h2>Login</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
      <label for="role">Role:</label>
      <select id="role" name="role">
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
      </select>

      <label for="username">Username:</label>
      <input type="text" id="username" name="username" required="required" />

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required="required" />

      <input type="submit" id="submit" value="Login">
	  
	  <br>
	  <center><p>No Account? <a href="signup.php">Register Here!</a></p></center>
    </form>
  </section>
</body>
</html>