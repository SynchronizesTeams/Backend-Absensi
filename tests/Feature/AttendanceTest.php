<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const OFFICE_LAT = -6.467017;
    private const OFFICE_LNG = 106.864356;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'user_id' => uniqid('user_'),
            'role' => $role,
            'no_induk' => fake()->unique()->numerify('##########'),
        ]);
    }

    /** @test */
    public function teacher_can_check_in_on_time()
    {
        $this->withoutExceptionHandling();
        $user = $this->createUser('guru');
        Carbon::setTestNow('2026-03-12 07:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/masuk', [
                'photo_masuk' => UploadedFile::fake()->create('masuk.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Absen masuk berhasil');

        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'keterangan_masuk' => 'hadir',
            'predikat' => 'Tepat Waktu',
            'tgl_masuk' => '2026-03-12',
        ]);

        // Coordinates should NOT be saved in DB
        $record = Absensi::where('user_id', $user->user_id)->first();
        $this->assertNull($record->latitude_masuk ?? null);
        $this->assertNull($record->longitude_masuk ?? null);
    }

    /** @test */
    public function teacher_check_in_late()
    {
        $user = $this->createUser('guru');
        Carbon::setTestNow('2026-03-12 08:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/masuk', [
                'photo_masuk' => UploadedFile::fake()->create('masuk.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'predikat' => 'Telat',
            'tgl_masuk' => '2026-03-12',
        ]);
    }

    /** @test */
    public function ob_can_check_in_on_time()
    {
        $user = $this->createUser('ob');
        Carbon::setTestNow('2026-03-12 06:45:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/masuk', [
                'photo_masuk' => UploadedFile::fake()->create('masuk.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'predikat' => 'Tepat Waktu',
            'tgl_masuk' => '2026-03-12',
        ]);
    }

    /** @test */
    public function security_day_shift_check_in()
    {
        $user = $this->createUser('satpam');
        // Pagi shift starts at 05:30
        Carbon::setTestNow('2026-03-12 06:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/masuk', [
                'photo_masuk' => UploadedFile::fake()->create('masuk.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12',
        ]);
    }

    /** @test */
    public function cannot_check_in_outside_radius()
    {
        $user = $this->createUser('guru');
        Carbon::setTestNow('2026-03-12 07:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/masuk', [
                'photo_masuk' => UploadedFile::fake()->create('masuk.jpg', 100),
                'latitude' => -7.0,
                'longitude' => 110.0,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Anda berada di luar area kantor');
    }

    /** @test */
    public function teacher_can_check_out()
    {
        $user = $this->createUser('guru');

        // Manual check-in in DB to test check-out
        Absensi::create([
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12',
            'jam_masuk' => '07:00:00',
            'keterangan_masuk' => 'hadir',
            'photo_masuk' => 'absensi/masuk.jpg',
        ]);

        Carbon::setTestNow('2026-03-12 14:00:00');
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/pulang', [
                'photo_pulang' => UploadedFile::fake()->create('pulang.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Absen pulang berhasil');

        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12',
            'tgl_pulang' => '2026-03-12',
            'keterangan_pulang' => 'normal',
        ]);

        $record = Absensi::where('user_id', $user->user_id)->first();
        $this->assertNotNull($record->jam_pulang);
        $this->assertNull($record->latitude_pulang ?? null);
        $this->assertNull($record->longitude_pulang ?? null);
    }

    /** @test */
    public function ob_can_check_out_overtime()
    {
        $user = $this->createUser('ob');

        Absensi::create([
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12',
            'jam_masuk' => '06:30:00',
            'keterangan_masuk' => 'hadir',
            'photo_masuk' => 'absensi/masuk.jpg',
        ]);

        Carbon::setTestNow('2026-03-12 20:00:00');
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/pulang', [
                'photo_pulang' => UploadedFile::fake()->create('pulang.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'keterangan_pulang' => 'lembur',
            'tgl_pulang' => '2026-03-12',
        ]);
    }

    /** @test */
    public function security_night_shift_check_out_cross_day()
    {
        $user = $this->createUser('satpam');

        // Mock shift malam rules: masuk 18:00, pulang 05:28 (next day)
        Carbon::setTestNow('2026-03-12 19:00:00');
        Absensi::create([
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12', // resolveTanggal for night shift at 19:00 is today
            'jam_masuk' => '19:00:00',
            'keterangan_masuk' => 'hadir',
            'photo_masuk' => 'absensi/masuk.jpg',
            'shift' => 'malam'
        ]);

        // Next Day early morning
        Carbon::setTestNow('2026-03-13 05:29:00');
        // resolveTanggal for night shift at 05:29 is yesterday (2026-03-12)

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/absen/pulang', [
                'photo_pulang' => UploadedFile::fake()->create('pulang.jpg', 100),
                'latitude' => self::OFFICE_LAT,
                'longitude' => self::OFFICE_LNG,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->user_id,
            'tgl_masuk' => '2026-03-12',
            'tgl_pulang' => '2026-03-12',
        ]);

        $record = Absensi::where('user_id', $user->user_id)->where('tgl_masuk', '2026-03-12')->first();
        $this->assertNotNull($record->jam_pulang);
        $this->assertEquals('2026-03-12', $record->tgl_pulang); // Based on resolveTanggal logic
    }
}
