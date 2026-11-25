<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AttendanceFaceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function setupEmployee(): User
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        DB::table('attendance_outlets')->insert(['id' => 1, 'name' => 'Outlet Uji', 'latitude' => -7.25, 'longitude' => 112.75, 'radius_meters' => 100]);
        DB::table('attendance_faces')->insert(['user_id' => $user->id, 'enrolled_by' => $user->id, 'template' => Crypt::encryptString(json_encode(array_fill(0, 128, 1)))]);
        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['request_id' => (string) Str::uuid(), 'type' => 'masuk', 'latitude' => -7.25, 'longitude' => 112.75, 'accuracy' => 10, 'photo' => UploadedFile::fake()->image('selfie.jpg', 400, 400)], $extra);
    }

    private function face(array $vector): void
    {
        $fake = Mockery::mock(AttendanceFaceVerifier::class)->makePartial();
        $fake->shouldReceive('embedding')->andReturn($vector);
        $this->app->instance(AttendanceFaceVerifier::class, $fake);
    }

    public function test_guest_cannot_submit_attendance(): void
    {
        $this->postJson('/api/mobile/attendance', [])->assertUnauthorized();
    }

    public function test_employee_cannot_change_outlet_or_enroll_faces(): void
    {
        $user = $this->setupEmployee();
        $this->postJson('/api/mobile/attendance/outlet', [])->assertForbidden();
        $this->postJson('/api/mobile/attendance/faces/'.$user->id, [])->assertForbidden();
    }

    public function test_radius_and_accuracy_are_checked_before_face_processing(): void
    {
        $this->setupEmployee();
        $fake = Mockery::mock(AttendanceFaceVerifier::class);
        $fake->shouldNotReceive('embedding');
        $this->app->instance(AttendanceFaceVerifier::class, $fake);
        $this->postJson('/api/mobile/attendance', $this->payload(['latitude' => -8]))->assertUnprocessable();
        $this->postJson('/api/mobile/attendance', $this->payload(['accuracy' => 90]))->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_wrong_face_is_rejected_without_storing_photo(): void
    {
        $this->setupEmployee();
        $this->face(array_fill(0, 128, -1));
        $this->postJson('/api/mobile/attendance', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('attendances', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_matching_face_records_attendance_and_retry_is_idempotent(): void
    {
        $this->setupEmployee();
        $this->face(array_fill(0, 128, 1));
        $payload = $this->payload();
        $this->postJson('/api/mobile/attendance', $payload)->assertCreated()->assertJsonPath('attendance.type', 'masuk')->assertJsonMissingPath('attendance.photo_path');
        $this->postJson('/api/mobile/attendance', $payload)->assertOk();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->postJson('/api/mobile/attendance', $this->payload())->assertConflict();
        $this->getJson('/api/mobile/attendance/status')->assertJsonPath('next_type', 'pulang');
        $this->postJson('/api/mobile/attendance', $this->payload(['type' => 'pulang']))->assertCreated();
        $this->getJson('/api/mobile/attendance/status')->assertJsonPath('next_type', null);
    }

    public function test_checkout_before_checkin_is_rejected(): void
    {
        $this->setupEmployee();
        $this->face(array_fill(0, 128, 1));
        $this->postJson('/api/mobile/attendance', $this->payload(['type' => 'pulang']))->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_missing_enrollment_is_rejected(): void
    {
        $this->setupEmployee();
        DB::table('attendance_faces')->delete();
        $this->postJson('/api/mobile/attendance', $this->payload())->assertUnprocessable();
    }

    public function test_engine_failure_does_not_record_attendance(): void
    {
        $this->setupEmployee();
        $fake = Mockery::mock(AttendanceFaceVerifier::class);
        $fake->shouldReceive('embedding')->andThrow(new \RuntimeException('Mesin belum siap.'));
        $this->app->instance(AttendanceFaceVerifier::class, $fake);
        $this->postJson('/api/mobile/attendance', $this->payload())->assertStatus(503);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_admin_can_configure_outlet_and_enroll_encrypted_template(): void
    {
        $user = $this->setupEmployee();
        $user->is_attendance_admin = true;
        $user->role = 'owner';
        $user->save();
        $this->face(array_fill(0, 128, 1));
        $this->postJson('/api/mobile/attendance/outlet', ['name' => 'Lokasi baru', 'latitude' => -7, 'longitude' => 112, 'accuracy' => 10, 'radius_meters' => 150])->assertOk();
        $this->postJson('/api/mobile/attendance/faces/'.$user->id, ['photo' => UploadedFile::fake()->image('wajah.jpg')])->assertOk();
        $encrypted = DB::table('attendance_faces')->where('user_id', $user->id)->value('template');
        $this->assertNotEquals(json_encode(array_fill(0, 128, 1)), $encrypted);
        $this->assertCount(128, json_decode(Crypt::decryptString($encrypted), true));
    }

    public function test_login_uses_actual_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('kata-sandi-uji')]);
        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'salah'])->assertUnprocessable();
        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'kata-sandi-uji'])->assertOk()->assertJsonStructure(['token', 'user']);
    }
}
