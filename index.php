<?php
// index.php - OPEN LOGIN (Accepts Any User)
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    
    // VALIDATION: Just check if they typed a name
    if (!empty($u)) {
        // SUCCESS: We don't verify password. We just trust them.
        // We pass their name to the app so reports are tagged correctly.
        echo "<script>
            localStorage.setItem('aegis_auth', 'true');
            localStorage.setItem('aegis_user', '" . htmlspecialchars($u) . "');
            window.location.href = 'app.php';
        </script>";
        exit;
    } else {
        $error = "Please enter a Callsign";
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
                <label class="block text-gray-500 text-xs font-bold mb-1">USERNAME</label>
                <input type="text" name="username" required autofocus class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 focus:border-blue-500 outline-none text-white" placeholder="e.g. Ranger-1">
            </div>
            <div>
                <label class="block text-gray-500 text-xs font-bold mb-1">SET PASSWORD</label>
                <input type="password" name="password" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 focus:border-blue-500 outline-none text-white" placeholder="(Required)">
            </div>
            <button class="w-full bg-blue-600 hover:bg-blue-500 font-bold py-4 rounded-xl transition-all mt-4">
                START SESSION
            </button>
        </form>
    </div>
</body>
</html>