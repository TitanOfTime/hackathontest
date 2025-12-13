<?php
require 'db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if (!empty($u) && !empty($p)) {
        
        // 1. Check if user already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$u]);
        $user = $stmt->fetch();

        if ($user) {
            // --- SCENARIO A: EXISTING USER (LOGIN) ---
            if (password_verify($p, $user['password'])) {
                // Password Matches -> LOG THEM IN
                $js_token = htmlspecialchars($u);
                echo "<script>
                    localStorage.setItem('aegis_auth', 'true');
                    localStorage.setItem('aegis_user', '$js_token');
                    window.location.href = 'app.php';
                </script>";
                exit;
            } else {
                // Password Wrong -> ERROR
                $error = "Incorrect password for '$u'.";
            }
        } else {
            // --- SCENARIO B: NEW USER (REGISTER) ---
            // Create the account automatically
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            
            if ($stmt->execute([$u, $hash])) {
                // Registration Success -> LOG THEM IN IMMEDIATELY
                $js_token = htmlspecialchars($u);
                echo "<script>
                    localStorage.setItem('aegis_auth', 'true');
                    localStorage.setItem('aegis_user', '$js_token');
                    window.location.href = 'app.php';
                </script>";
                exit;
            } else {
                $error = "Database error. Try a different name.";
            }
        }

    } else {
        $error = "Username and Password are required.";
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
        <p class="text-gray-400 mb-8 text-center text-sm uppercase tracking-widest">Field Access</p>
        
        <?php if($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded-lg mb-6 text-center text-sm">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-gray-800/50 p-6 rounded-2xl border border-gray-700">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-500 text-xs font-bold mb-1">USERNAME</label>
                    <input type="text" name="username" required class="w-full bg-gray-900 border border-gray-600 rounded-lg p-3 focus:border-blue-500 outline-none text-white transition-colors" placeholder="Create or Enter Name">
                </div>
                <div>
                    <label class="block text-gray-500 text-xs font-bold mb-1">PASSWORD</label>
                    <input type="password" name="password" required class="w-full bg-gray-900 border border-gray-600 rounded-lg p-3 focus:border-blue-500 outline-none text-white transition-colors" placeholder="Create or Verify Pass">
                </div>
                
                <button class="w-full bg-blue-600 hover:bg-blue-500 font-bold py-4 rounded-xl transition-all mt-2 shadow-lg">
                    ENTER SYSTEM
                </button>
            </form>
            <p class="mt-4 text-center text-gray-500 text-xs">
                New name? We'll create an account.<br>Existing name? Enter your password.
            </p>
        </div>
    </div>
</body>
</html>