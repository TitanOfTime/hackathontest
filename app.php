<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aegis Responder</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { touch-action: manipulation; }</style>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <div id="status-bar" class="p-3 text-center font-bold text-sm bg-green-600 transition-colors duration-300">
        ONLINE - SYNC READY
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

            <input type="hidden" id="lat" value="0"><input type="hidden" id="lng" value="0">

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 active:bg-blue-700 py-5 rounded-xl font-bold text-xl shadow-lg transition-transform transform active:scale-95">
                SUBMIT REPORT
            </button>
        </form>

        <div class="mt-8 p-4 bg-gray-800 rounded-xl border border-gray-700 text-center">
            <p class="text-gray-400 text-sm">Offline Queue</p>
            <p class="text-3xl font-bold text-yellow-400" id="queue-count">0</p>
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        // Register PWA Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</body>
</html>