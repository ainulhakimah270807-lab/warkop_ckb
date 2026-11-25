<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceFaceVerifier;
use App\Services\AttendanceLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:255']);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }
        return response()->json(['token' => $user->createToken('mobile-presensi', ['*'], now()->addDays(7))->plainTextToken, 'user' => $this->userData($user)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Berhasil keluar.']);
    }

    private function userData(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'is_attendance_admin' => $user->role === 'owner'];
    }

    public function status(Request $request)
    {
        $today = now(config('attendance.timezone'))->toDateString();
        $records = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->orderBy('id')->get();
        return response()->json([
            'user' => $this->userData($request->user()),
            'face_enrolled' => DB::table('attendance_faces')->where('user_id', $request->user()->id)->exists(),
            'outlet' => DB::table('attendance_outlets')->find(1),
            'next_type' => $records->contains('type', 'pulang') ? null : ($records->contains('type', 'masuk') ? 'pulang' : 'masuk'),
            'records' => $records,
        ]);
    }

    public function checkLocation(Request $request, AttendanceLocation $location)
    {
        $data = $request->validate($this->locationRules());
        $outlet = DB::table('attendance_outlets')->find(1);
        abort_unless($outlet, 422, 'Titik outlet belum diatur pengelola.');
        $distance = $location->validate($outlet, $data['latitude'], $data['longitude'], $data['accuracy']);
        return response()->json(['distance_meters' => $distance, 'outlet' => $outlet]);
    }

    private function locationRules(): array
    {
        return ['latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'accuracy' => 'required|numeric|gt:0|lte:50'];
    }

    public function store(Request $request, AttendanceFaceVerifier $faces, AttendanceLocation $location)
    {
        $data = $request->validate($this->locationRules() + [
            'request_id' => 'required|uuid', 'type' => 'required|in:masuk,pulang',
            'photo' => 'required|file|image|mimes:jpg,jpeg,png|max:5120|dimensions:max_width=4096,max_height=4096',
        ]);
        $user = $request->user();
        $existing = Attendance::where('request_id', $data['request_id'])->where('user_id', $user->id)->first();
        if ($existing) return response()->json(['attendance' => $existing]);
        $outlet = DB::table('attendance_outlets')->find(1);
        abort_unless($outlet, 422, 'Titik outlet belum diatur pengelola.');
        $distance = $location->validate($outlet, $data['latitude'], $data['longitude'], $data['accuracy']);
        $template = DB::table('attendance_faces')->where('user_id', $user->id)->first();
        abort_unless($template, 422, 'Wajah Anda belum didaftarkan pengelola.');
        try {
            $probe = $faces->embedding($request->file('photo')->getRealPath());
            $score = $faces->similarity(json_decode(Crypt::decryptString($template->template), true), $probe);
        } catch (\RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 503);
        }
        if ($score < config('attendance.face_threshold')) {
            throw ValidationException::withMessages(['photo' => 'Wajah tidak cocok dengan karyawan yang masuk. Ambil ulang foto dengan pencahayaan yang cukup.']);
        }
        $path = null;
        try {
            $record = DB::transaction(function () use ($user, $request, $data, $outlet, $distance, $score, &$path) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existing = Attendance::where('request_id', $data['request_id'])->where('user_id', $user->id)->first();
                if ($existing) return $existing;
                $date = now(config('attendance.timezone'))->toDateString();
                $records = Attendance::where('user_id', $user->id)->where('work_date', $date)->get();
                abort_if($records->contains('type', $data['type']), 409, 'Presensi ini sudah tercatat hari ini.');
                abort_if($data['type'] === 'pulang' && !$records->contains('type', 'masuk'), 422, 'Lakukan presensi masuk terlebih dahulu.');
                $path = $request->file('photo')->store('private/attendance/'.$user->id, 'local');
                abort_unless($path, 503, 'Foto gagal disimpan. Coba lagi.');
                return Attendance::create([
                    'user_id' => $user->id, 'attendance_outlet_id' => $outlet->id,
                    'request_id' => $data['request_id'], 'work_date' => $date, 'type' => $data['type'],
                    'recorded_at' => now(), 'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'], 'distance_meters' => $distance, 'face_score' => $score, 'photo_path' => $path,
                ]);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return response()->json(['attendance' => $record], 201);
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'owner', 403, 'Hanya Owner yang boleh mengubah pengaturan presensi.');
    }

    public function employees(Request $request)
    {
        $this->requireAdmin($request);
        return response()->json(['employees' => User::select('id', 'name', 'email')->orderBy('name')->get()]);
    }

    public function saveOutlet(Request $request)
    {
        $this->requireAdmin($request);
        $data = $request->validate($this->locationRules() + ['name' => 'required|string|max:100', 'radius_meters' => 'required|integer|between:50,1000']);
        unset($data['accuracy']);
        DB::table('attendance_outlets')->updateOrInsert(['id' => 1], $data + ['updated_at' => now(), 'created_at' => now()]);
        return response()->json(['message' => 'Titik dan radius outlet disimpan.']);
    }

    public function enroll(Request $request, User $user, AttendanceFaceVerifier $faces)
    {
        $this->requireAdmin($request);
        $request->validate(['photo' => 'required|file|image|mimes:jpg,jpeg,png|max:5120|dimensions:max_width=4096,max_height=4096']);
        try {
            $embedding = $faces->embedding($request->file('photo')->getRealPath());
        } catch (\RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 503);
        }
        DB::table('attendance_faces')->updateOrInsert(['user_id' => $user->id], [
            'template' => Crypt::encryptString(json_encode($embedding)), 'enrolled_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['message' => 'Wajah '.$user->name.' berhasil didaftarkan.']);
    }
}
