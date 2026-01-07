<?php
require 'db.php';
$message = $_GET['msg'] ?? "";

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && password_verify($_POST['password'], $res['password'])) {
        $_SESSION['user_id'] = $res['id'];
        $_SESSION['username'] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "error|Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Payroll Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: white; }
        .glass { background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .glass-input { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white; outline: none; }
    </style>
</head>
<body class="flex items-center justify-center p-6">
    <div class="glass p-10 rounded-3xl w-full max-w-md text-center">
        <h1 class="text-4xl font-bold mb-2 tracking-tight">Welcome Back</h1>
        <p class="text-blue-100 opacity-70 mb-8 text-sm">Enter your credentials to manage payroll</p>
        
        <?php echo getMessage($message); ?>

        <form method="post" class="space-y-4">
            <input type="text" name="username" placeholder="Username" class="glass-input w-full p-4 rounded-xl" required>
            <input type="password" name="password" placeholder="Password" class="glass-input w-full p-4 rounded-xl" required>
            <button type="submit" name="login" class="w-full bg-white text-indigo-700 font-bold p-4 rounded-xl hover:scale-105 transition">Sign In</button>
        </form>
        
        <p class="mt-6 text-sm opacity-80">New here? <a href="register.php" class="font-bold underline text-blue-200">Register now</a></p>
    </div>
</body>
</html>