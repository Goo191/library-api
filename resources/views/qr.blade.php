<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic QR Style</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 90%;
            width: 400px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        #qr {
            width: 300px;
            height: 300px;
            border: 1px solid #ddd;
            transition: opacity 0.5s;
            margin: 0 auto;
            display: block;
        }
        
        .fade-effect {
            animation: fade 2s infinite;
        }
        
        @keyframes fade {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }
        
        .update-info {
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>made by:yousef tarek</h2>
        <img id="qr" src="{{ asset('storage/qrcodes/library_qr_code.png') }}" 
             class="fade-effect" alt="Library QR Code">
        <div class="update-info">
            Last update: <span id="update-time"></span>
        </div>
    </div>

    <script>
function updateQRStyle() {
    // استدعاء API لتوليد الكيو آر أولاً
    fetch('/generate-qr')
        .then(() => {
            const img = document.getElementById('qr');
            const timestamp = new Date().getTime();
            img.src = "{{ asset('storage/qrcodes/library_qr_code.png') }}?v=" + timestamp;
            img.style.animation = 'none';
            void img.offsetWidth;
            document.getElementById('update-time').textContent = new Date().toLocaleTimeString();
        });
}
function handleQRError() {
    // إعادة المحاولة بعد ثانية إذا فشل التحميل
    setTimeout(updateQRStyle, 1000);
}
setInterval(updateQRStyle, 10000);
window.addEventListener('load', updateQRStyle);
</script>
</body>
</html>