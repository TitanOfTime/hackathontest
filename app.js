// app.js - FINAL VERSION WITH CAMERA

// --- GLOBAL VARIABLES ---
let compressedImageString = ""; // Stores the base64 image

// --- 1. AUTH LOGIC ---
const loginView = document.getElementById('login-view');
const appView = document.getElementById('app-view');
const currentUser = localStorage.getItem('aegis_user');

if (currentUser && loginView) {
    showApp(currentUser);
} else if (loginView) {
    loginView.classList.remove('hidden');
}

if(document.getElementById('login-form')) {
    document.getElementById('login-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const badge = document.getElementById('badge-id').value;
        if(badge.length > 0) {
            localStorage.setItem('aegis_user', badge);
            showApp(badge);
        }
    });
}

function showApp(badge) {
    loginView.classList.add('hidden');
    appView.classList.remove('hidden');
    if(document.getElementById('display-badge')) document.getElementById('display-badge').innerText = badge;
}

function logout() {
    if(confirm("End session?")) {
        localStorage.removeItem('aegis_user');
        location.reload();
    }
}

// --- 2. CAMERA & COMPRESSION ---
if(document.getElementById('cameraInput')) {
    document.getElementById('cameraInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.readAsDataURL(file);
        
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // FORCE RESIZE (Max 500px)
                const MAX_WIDTH = 500;
                const scaleSize = MAX_WIDTH / img.width;
                canvas.width = MAX_WIDTH;
                canvas.height = img.height * scaleSize;
                
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // COMPRESS to JPEG 50%
                compressedImageString = canvas.toDataURL('image/jpeg', 0.5);
                
                // Show Preview
                document.getElementById('preview-img').src = compressedImageString;
                document.getElementById('preview-area').classList.remove('hidden');
            }
        }
    });
}

// --- 3. FORM SUBMISSION ---
const form = document.getElementById('incident-form');
if (form) {
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const report = {
            uuid: crypto.randomUUID(),
            type: document.getElementById('type').value,
            severity: document.getElementById('severity').value,
            lat: document.getElementById('lat').value,
            lng: document.getElementById('lng').value,
            timestamp: new Date().toISOString(),
            image: compressedImageString || null // <--- SEND IMAGE HERE
        };

        let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
        queue.push(report);
        localStorage.setItem('aegis_queue', JSON.stringify(queue));
        
        alert("Report Saved!");
        form.reset();
        document.getElementById('preview-area').classList.add('hidden'); // Hide preview
        compressedImageString = ""; // Reset image
        updateQueueUI();
        
        if(navigator.onLine) trySync();
    });
}

// --- 4. GPS & SYNC ---
navigator.geolocation.getCurrentPosition(
    pos => { 
        if(document.getElementById('lat')) {
            document.getElementById('lat').value = pos.coords.latitude;
            document.getElementById('lng').value = pos.coords.longitude;
        }
    },
    err => console.log("GPS Fail", err),
    { enableHighAccuracy: true }
);

async function trySync() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    if (queue.length === 0) return;

    // Update UI
    const bar = document.getElementById('status-bar');
    if(bar) bar.textContent = "UPLOADING...";

    try {
        let res = await fetch('sync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(queue)
        });
        
        if (res.ok) {
            localStorage.setItem('aegis_queue', "[]");
            updateQueueUI();
            updateStatus();
        }
    } catch (e) { 
        console.log("Sync failed.");
        updateStatus();
    }
}

function updateStatus() {
    const bar = document.getElementById('status-bar');
    if(!bar) return;
    if (navigator.onLine) {
        bar.textContent = "ONLINE - SYNC READY";
        bar.className = "p-3 text-center font-bold text-sm bg-green-600 text-white";
        trySync();
    } else {
        bar.textContent = "OFFLINE - SAVING LOCALLY";
        bar.className = "p-3 text-center font-bold text-sm bg-red-600 text-white";
    }
}

function updateQueueUI() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    if(document.getElementById('queue-count')) {
        document.getElementById('queue-count').innerText = queue.length;
    }
}

window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);
window.addEventListener('load', () => { updateStatus(); updateQueueUI(); });