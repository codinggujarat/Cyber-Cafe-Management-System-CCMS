<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Order QR | Cyber Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        #reader { width: 100%; border-radius: 1rem; overflow: hidden; }
        #reader video { object-fit: cover; border-radius: 1rem; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -left-20 w-96 h-96 bg-indigo-200 rounded-full blur-3xl opacity-30"></div>
        <div class="absolute top-1/2 right-10 w-72 h-72 bg-purple-200 rounded-full blur-3xl opacity-30"></div>
    </div>

    <div class="glass w-full max-w-md p-6 rounded-3xl shadow-xl border border-white/50 relative z-10">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-black text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-gray-200">
                <i class="fas fa-qrcode text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Track Your Order</h1>
            <p class="text-sm text-gray-500 mt-2">Scan the QR code on your invoice to view live status.</p>
        </div>

        <!-- Tabs -->
        <div class="flex p-1 bg-gray-100 rounded-xl mb-6">
            <button onclick="switchTab('camera')" id="btn-camera" class="flex-1 py-2 rounded-lg text-sm font-bold bg-white shadow-sm text-black transition-all">
                <i class="fas fa-camera mr-2"></i> Camera
            </button>
            <button onclick="switchTab('file')" id="btn-file" class="flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:text-black transition-all">
                <i class="fas fa-image mr-2"></i> Upload Image
            </button>
        </div>

        <!-- Camera Section -->
        <div id="camera-section" class="relative">
            <div id="reader" class="bg-black aspect-square flex items-center justify-center text-gray-500">
                <p>Initialize Camera...</p>
            </div>
            <p id="cam-status" class="text-center text-xs text-gray-400 mt-2">Requesting camera permissions...</p>
        </div>

        <!-- File Section -->
        <div id="file-section" class="hidden text-center py-10 border-2 border-dashed border-gray-300 rounded-2xl hover:border-black transition-colors cursor-pointer relative" onclick="document.getElementById('qr-input-file').click()">
            <input type="file" id="qr-input-file" accept="image/*" class="hidden" onchange="handleFileUpload(this)">
            <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm font-bold text-gray-700">Click to Upload QR</p>
            <p class="text-xs text-gray-400 mt-1">Supports JPG, PNG, WEBP</p>
        </div>

        <!-- Result -->
        <div id="result-container" class="hidden mt-6 text-center">
            <div class="animate-pulse">
                <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl mb-2"></i>
                <p class="text-sm font-bold text-gray-900">QR Found! Redirecting...</p>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="index.php" class="text-sm font-bold text-gray-400 hover:text-black transition">
                <i class="fas fa-arrow-left mr-1"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        const html5QrCode = new Html5Qrcode("reader");
        let isCameraRunning = false;
        let isProcessing = false;

        function handleScanResult(decodedText) {
            if (isProcessing) return;
            isProcessing = true;

            const processUrl = () => {
                let cleanUrl = decodedText.trim();
                
                try {
                    // Try to construct URL to parse parameters
                    const url = new URL(cleanUrl);
                    
                    if (cleanUrl.includes('track_public.php')) {
                        const hash = url.searchParams.get('hash');
                        if (!hash) {
                            alert("Invalid QR Code: The tracking ID is missing.\n\nPlease generate a new QR code from the Invoice page.");
                            location.reload();
                            return;
                        }
                        
                        document.getElementById('result-container').classList.remove('hidden');
                        setTimeout(() => window.location.href = cleanUrl, 500);
                    } else {
                         alert("Invalid QR Code: Not a valid tracking link.");
                         location.reload();
                    }
                } catch (e) {
                    // Fallback for simple string check
                    if (cleanUrl.includes('track_public.php') && cleanUrl.includes('hash=')) {
                        // Validate hash value isn't empty
                        if(cleanUrl.split('hash=')[1].trim() === '') {
                             alert("Invalid QR Code: The tracking ID is missing.\n\nPlease generate a new QR code from the Invoice page.");
                             location.reload();
                             return;
                        }
                        document.getElementById('result-container').classList.remove('hidden');
                        setTimeout(() => window.location.href = cleanUrl, 500);
                    } else {
                        alert("Invalid Data: " + cleanUrl);
                        location.reload();
                    }
                }
            };

            // If camera is running, stop it first
            if (isCameraRunning) {
                html5QrCode.stop().then(() => {
                    isCameraRunning = false;
                    processUrl();
                }).catch(err => {
                    console.error("Stop failed", err);
                    isCameraRunning = false;
                    processUrl(); // Proceed anyway
                });
            } else {
                processUrl();
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            handleScanResult(decodedText);
        }

        function startCamera() {
            if (isCameraRunning) return;
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
            .then(() => {
                isCameraRunning = true;
                document.getElementById('cam-status').innerText = "Camera active. Point at QR code.";
                document.getElementById('cam-status').className = "text-center text-xs text-green-600 mt-2 font-bold";
            })
            .catch(err => {
                document.getElementById('cam-status').innerText = "Camera Error: " + err;
                document.getElementById('cam-status').classList.add('text-red-500');
            });
        }

        function stopCamera() {
            return new Promise((resolve, reject) => {
                if (isCameraRunning) {
                    html5QrCode.stop().then(() => {
                        isCameraRunning = false;
                        document.getElementById('cam-status').innerText = "Camera paused.";
                        resolve();
                    }).catch(err => {
                        console.error(err);
                        isCameraRunning = false; // Force state reset
                        resolve();
                    });
                } else {
                    resolve();
                }
            });
        }

        function switchTab(tab) {
            if (tab === 'camera') {
                document.getElementById('camera-section').classList.remove('hidden');
                document.getElementById('file-section').classList.add('hidden');
                
                document.getElementById('btn-camera').className = "flex-1 py-2 rounded-lg text-sm font-bold bg-white shadow-sm text-black transition-all";
                document.getElementById('btn-file').className = "flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:text-black transition-all";

                startCamera();
            } else {
                // Must stop camera before hiding UI or switching mode
                stopCamera().then(() => {
                    document.getElementById('camera-section').classList.add('hidden');
                    document.getElementById('file-section').classList.remove('hidden');

                    document.getElementById('btn-file').className = "flex-1 py-2 rounded-lg text-sm font-bold bg-white shadow-sm text-black transition-all";
                    document.getElementById('btn-camera').className = "flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:text-black transition-all";
                });
            }
        }

        function handleFileUpload(input) {
            if (input.files.length === 0) return;
            const imageFile = input.files[0];
            
            // Ensure camera is stopped before scanning file to avoid conflict
            stopCamera().then(() => {
                html5QrCode.scanFile(imageFile, true)
                .then(decodedText => {
                    handleScanResult(decodedText);
                })
                .catch(err => {
                    alert("Error scanning file: " + err);
                    input.value = ''; // Reset input
                });
            });
        }

        // Auto-start camera on load
        startCamera();
    </script>
</body>
</html>
