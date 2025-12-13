<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Aegis - Live</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-4">

    <div class="text-center max-w-lg w-full">
        <h1 class="text-5xl font-bold mb-2 tracking-tighter text-blue-500">AEGIS</h1>
        <p class="text-gray-400 mb-12 uppercase tracking-widest text-sm">Disaster Response System</p>

        <div class="mb-8">
            <?php
            require 'db.php';
            if (isset($conn)) {
                echo '<span class="bg-green-900 text-green-300 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">● System Online</span>';
            } else {
                echo '<span class="bg-red-900 text-red-300 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">● System Offline</span>';
            }
            ?>
        </div>
        
        <div class="space-y-4">
            <a href="app.php" class="block w-full bg-blue-600 hover:bg-blue-500 p-6 rounded-2xl transition-transform transform active:scale-95 shadow-lg border border-blue-500">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <h2 class="text-2xl font-bold">Field Responder App</h2>
                        <p class="text-blue-200 text-sm">Mobile Data Collection</p>
                    </div>
                    <span class="text-4xl">📱</span>
                </div>
            </a>
            
            <a href="dashboard.php" class="block w-full bg-gray-800 hover:bg-gray-700 p-6 rounded-2xl transition-transform transform active:scale-95 shadow-lg border border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <h2 class="text-2xl font-bold">HQ Dashboard</h2>
                        <p class="text-gray-400 text-sm">Live Command Map</p>
                    </div>
                    <span class="text-4xl">🗺️</span>
                </div>
            </a>
        </div>

        <p class="mt-12 text-gray-600 text-xs">Aegis v1.0 // Hackathon Build</p>
    </div>

</body>
</html>