<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\students;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class QrLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create fake storage disk
        Storage::fake('public');
        
        // Store a fake QR code image
        Storage::disk('public')->put(
            'qr_codes/library_entrance.png',
            file_get_contents(base_path('tests/fixtures/qr_codes/library_entrance.png'))
        );
    }

    public function test_student_can_login_with_valid_qr_code()
    {
        // Create a test student
        $student = students::factory()->create([
            'name' => 'يوسف طارق',
            'student_id' => '12345'
        ]);

        // Authenticate the student first (as if they logged in with credentials)
        $this->actingAs($student, 'sanctum');

        // Simulate QR code scan by sending the scanned QR code content
        $response = $this->postJson('/api/qr-login', [
            'qr_content' => 'library_entrance_qr_content'
        ]);

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert a QR log was created in the database
        $this->assertDatabaseHas('qr_logs', [
            'student_id' => $student->id,
            'check_in' => now()->toDateTimeString(),
            'check_out' => null
        ]);

        // Assert the response contains the correct data
        $response->assertJson([
            'message' => 'تم تسجيل دخولك للمكتبة بنجاح',
            'check_in' => true
        ]);
    }

    public function test_student_cannot_login_with_invalid_qr_code()
    {
        $student = students::factory()->create();
        $this->actingAs($student, 'sanctum');

        // Try to login with invalid QR content
        $response = $this->postJson('/api/qr-login', [
            'qr_content' => 'invalid_qr_content'
        ]);

        // Assert unauthorized
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'رمز QR غير صالح'
            ]);
    }

    public function test_student_cannot_login_with_qr_if_not_authenticated()
    {
        // Try to scan QR without being logged in
        $response = $this->postJson('/api/qr-login', [
            'qr_content' => 'library_entrance_qr_content'
        ]);

        // Assert unauthorized
        $response->assertStatus(401);
    }

    public function test_student_can_checkout_from_library()
    {
        // Create a test student
        $student = students::factory()->create();
        
        // Authenticate the student
        $this->actingAs($student, 'sanctum');

        // Create an active QR log (as if they're already in the library)
        $student->qrLogs()->create([
            'check_in' => now(),
            'check_out' => null
        ]);

        // Simulate QR code scan for checkout
        $response = $this->postJson('/api/qr-logout', [
            'qr_content' => 'library_entrance_qr_content'
        ]);

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the QR log was updated with checkout time
        $this->assertDatabaseHas('qr_logs', [
            'student_id' => $student->id,
            'check_out' => now()->toDateTimeString()
        ]);

        // Assert the response contains the correct data
        $response->assertJson([
            'message' => 'تم تسجيل خروجك من المكتبة بنجاح',
            'check_out' => true
        ]);
    }
} 