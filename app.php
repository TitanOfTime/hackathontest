<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aegis Responder</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { touch-action: manipulation; }</style>
    <script>
        // SECURITY CHECK: If no token, kick back to index.php
        if (!localStorage.getItem('aegis_auth')) {
            window.location.href = 'index.php';
        }
    </script>
</head>
<body class="bg-gray-900 text-white min-h-screen font-sans">

    <div id="app-view">
        
        <div id="status-bar" class="p-3 text-center font-bold text-sm bg-gray-600 text-gray-200 transition-colors duration-300">
            CHECKING CONNECTION...
        </div>

        <div class="flex justify-between items-center p-4 bg-gray-800">
            <span class="font-bold text-gray-300">USER: <span id="display-badge">...</span></span>
            <button onclick="logout()" class="text-xs text-red-400 border border-red-900 px-2 py-1 rounded hover:bg-red-900">LOGOUT</button>
        </div>

        <div class="p-6 max-w-md mx-auto mt-4">
            <h1 class="text-3xl font-bold mb-6 text-gray-100">Report Incident</h1>
            
            <form id="incident-form" class="space-y-6">
                <div>
                    <label class="block mb-2 text-gray-400">Incident Type</label>
                    <select id="type" class="w-full p-4 rounded-xl bg-gray-800 border-2 border-gray-700 text-lg focus:border-blue-500 outline-none">
                        <option value="Landslide">Landslide</option>
                        <option value="Flood">Flood</option>
                        <option value="Road Blocked">Road Blocked</option>
                        <option value="Power Down">Power Line Down</option>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 text-gray-400">Severity (1-5)</label>
                    <input type="range" id="severity" min="1" max="5" value="3" class="w-full h-3 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                    <div class="flex justify-between text-xs text-gray-500 mt-1"><span>Minor</span><span>Critical</span></div>
                </div>

                <input type="hidden" id="lat" value="0">
                <input type="hidden" id="lng" value="0">

                <div class="mb-6">
                    <label class="block mb-2 text-gray-400">Photo Evidence</label>
                    
                    <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">
                    
                    <button type="button" onclick="document.getElementById('cameraInput').click()" 
                            class="w-full bg-gray-800 hover:bg-gray-700 text-blue-400 border border-blue-900 border-dashed p-4 rounded-xl flex items-center justify-center gap-2 transition-all">
                        <span class="text-2xl">📷</span>
                        <span class="font-bold">Attach Photo</span>
                    </button>
                    
                    <div id="preview-area" class="mt-3 hidden">
                        <p class="text-xs text-green-400 mb-1 text-center">✓ Photo Compressed & Ready</p>
                        <img id="preview-img" class="w-full h-40 object-cover rounded-lg border border-gray-600">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 active:bg-blue-700 py-5 rounded-xl font-bold text-xl shadow-lg transition-transform transform active:scale-95">
                    SUBMIT REPORT
                </button>
            </form>

            <div class="mt-8 p-4 bg-gray-800 rounded-xl border border-gray-700 text-center">
                <p class="text-gray-400 text-sm">Offline Queue</p>
                <p class="text-3xl font-bold text-yellow-400" id="queue-count">0</p>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        // Init Display Name
        document.getElementById('display-badge').innerText = localStorage.getItem('aegis_user') || 'Unknown';

        // FORCE LOGOUT TO REDIRECT TO INDEX.PHP
        function logout() {
            if(confirm("End Session?")) {
                localStorage.removeItem('aegis_auth');
                localStorage.removeItem('aegis_user');
                window.location.href = 'index.php'; // <--- The Fix
            }
        }

        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
</body>
</html>