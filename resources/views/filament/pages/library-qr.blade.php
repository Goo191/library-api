<x-filament::page>
    <div class="flex flex-col items-center justify-start min-h-screen bg-black overflow-hidden pt-20">
        <div class="text-center p-8 bg-black rounded-xl shadow-md">

<h2 class="text-white text-2xl mb-6">made by: <span class="colorful-text" style="color: red;">yousef tarek</span></h2>

            <img id="qr" src="{{ asset('storage/qrcodes/library_qr_code.png') }}"
                 class="w-96 h-96 fade-effect mx-auto border border-gray-300"
                 alt="Library QR Code">

            <div class="update-info text-white mt-4 text-sm">
                Last update: <span id="update-time"></span>
            </div>
        </div>
    </div>

    <style>
    .fade-effect {
        animation: fade 2s infinite;
    }

    @keyframes fade {
        0% { opacity: 0.8; }
        50% { opacity: 1; }
        100% { opacity: 0.8; }
    }

    .colorful-text {
        background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: bold;
        font-size: 1em;
        animation: rainbow-move 3s infinite linear alternate; /* استخدام alternate للحركة في الاتجاهين */
        background-size: 200% 100%; /* لجعل التدرج أكبر من النص */
        display: inline-block;
    }

    @keyframes rainbow-move {
        0% { background-position: 0% 50%; }
        100% { background-position: 100% 50%; }
    }

    body {
        overflow: hidden !important;
    }
</style>

<script>
    const nameElement = document.querySelector('.colorful-text');
    const colors = ['red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'violet'];
    let colorIndex = 0;

    function changeTextColor() {
        nameElement.style.color = colors[colorIndex];
        colorIndex = (colorIndex + 1) % colors.length;
    }

    function updateQRStyle() {
        fetch('/generate-qr').then(() => {
            const img = document.getElementById('qr');
            const timestamp = new Date().getTime();
            img.src = "{{ asset('storage/qrcodes/library_qr_code.png') }}?v=" + timestamp;
            img.style.animation = 'none';
            void img.offsetWidth;
            document.getElementById('update-time').textContent = new Date().toLocaleTimeString();
        });
    }

    setInterval(changeTextColor, 500); // تغيير اللون كل نصف ثانية (يمكنك تعديل هذه القيمة)
    setInterval(updateQRStyle, 10000);
    window.addEventListener('load', updateQRStyle);
</script>
</x-filament::page>
