<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;

class GenerateLibraryQRCode extends Command
{
    protected $signature = 'library:generate-qr';
    protected $description = 'Generate a new QR code for library login';

    public function handle()
    {
        for ($i = 0; $i < 12; $i++) {
            // 1. إنشاء محتوى QR
            $content = now()->format('Y-m-d H:i:s');            
            // 2. حفظ المحتوى في ملف نصي
            Storage::put('qr/current.txt', $content);
            
            // 3. إنشاء QR Code
            $qrCode = new QrCode(
                data: $content,
                size:300,
                margin:10
            );
           
            
            // 4. حفظ الصورة
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            // التأكد من وجود المجلد
            $directory = public_path('qrcodes');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            
            // حفظ الصورة (طريقة واحدة فقط)
            file_put_contents($directory.'/library_qr_code.png', $result->getString());
            
            $this->info('QR Code updated successfully at '.now());
            sleep(5);
        }
    }
    
}