<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
	$role = $_POST['role'];


    if (!empty($username) && !empty($password) && !empty($role == 'student' || $role == 'teacher')) {

$stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

$stmt->execute([$username, $password, $role]);  

	echo '<style>
	.accountcreation-success {
		position: fixed; top: 20px; right: 20px; z-index: 9999;
		color: white; background-color: #9acd32; width: 500px; height: 30px;
		border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.2);
		padding: 20px; text-align: center; line-height: 1.5; border: 1px solid #7cb518;
		animation: successInOut 4s ease-in-out forwards;
	}
	@keyframes successInOut {
		0% { transform: translateX(100%) scale(0.8); opacity: 0; }
		15% { transform: translateX(0) scale(1); opacity: 1; }
		85% { transform: translateX(0) scale(1); opacity: 1; }
		100% { transform: translateX(100%) scale(0.8); opacity: 0; }
	}
	</style>
	<div class="accountcreation-success">User registered successfully! 🎉</div>';
	}else{
	echo '<style>
	.accountcreation-failed {
		position: fixed; top: 20px; right: 20px; z-index: 9999;
		color: white; background-color: red; width: 500px; height: 30px;
		border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.2);
		padding: 20px; text-align: center; line-height: 1.5; border: 1px solid #7cb518;
		animation: failedInOut 4s ease-in-out forwards;
	}
	@keyframes failedInOut {
		0% { transform: translateX(100%) scale(0.8); opacity: 0; }
		15% { transform: translateX(0) scale(1); opacity: 1; }
		85% { transform: translateX(0) scale(1); opacity: 1; }
		100% { transform: translateX(100%) scale(0.8); opacity: 0; }
	}
	</style>
	<div class="accountcreation-failed">User registered successfully! 🎉</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <style>
        .error { color: red; }
    </style>
	<title>Signup</title>
	<link rel="stylesheet" href="styles/style.css">
</head>
<body>
<section>
<h1>Create New Account</h1><hr><br>
<form method="POST" action="<?php echo(htmlspecialchars($_SERVER["PHP_SELF"]))?>">
     <label for="role">Role:</label>
      <select id="role" name="role">
        <option value="teacher">Teacher</option>
        <option value="student">Student</option>
		</select>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required="required" /> 
    <br><br>
	
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required="required"/> 
    <br><br>

    <input type="submit" id="submit" value="Register">
	<?php
		echo
		'<center>
		<a href="login.php"
		style="display: inline-block; padding: 12px 24px; background: #4CAF50; 
		color: white; text-decoration: none; border-radius: 8px; 
		box-shadow: 0 4px 12px rgba(0,0,0,0.2); font-weight: bold;
		margin: 20px auto; text-align: center; transition: all 0.3s;
		border: none; cursor: pointer;
		":hover { background: #45a049; transform: translateY(-2px); }
		">← Return to Login</a>
		</center>';
	?>
</form>
</section>
</body>
</html>

