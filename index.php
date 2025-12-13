<?php
require 'db.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';

    // DATABASE CHECK
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'responder'");
    $stmt->execute([$u]);
    $user = $stmt->fetch();

    if ($user && password_verify($p, $user['password'])) {
        // Login Success: Save Token to LocalStorage for Offline App
        echo "<script>
            localStorage.setItem('aegis_auth', 'true');
            localStorage.setItem('aegis_user', '" . htmlspecialchars($u) . "');
            window.location.href = 'app.php';
        </script>";
        exit;
    } else {
        $error = "Invalid Responder Credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-6">
    <div class="max-w-sm w-full">
        <h1 class="text-4xl font-bold mb-2 text-blue-500 text-center">AEGIS</h1>
        <p class="text-gray-400 mb-8 text-center text-sm uppercase tracking-widest">Field Responder Access</p>
        
        <?php if($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded-lg mb-4 text-center text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-500 text-xs font-bold mb-1">CALLSIGN</label>
                <input type="text" name="username" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 focus:border-blue-500 outline-none text-white" placeholder="responder">
            </div>
            <div>
                <label class="block text-gray-500 text-xs font-bold mb-1">PASSCODE</label>
                <input type="password" name="password" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 focus:border-blue-500 outline-none text-white" placeholder="••••">
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-500 font-bold py-4 rounded-xl transition-all mt-4">
                INITIATE SESSION
            </button>
        </form>
    </div>
</body>
</html>