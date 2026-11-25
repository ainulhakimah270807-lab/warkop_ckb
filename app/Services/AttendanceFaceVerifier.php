<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AttendanceFaceVerifier
{
    public function embedding(string $path): array
    {
        $environment = null;
        if (PHP_OS_FAMILY === 'Windows') {
            // Artisan's HTTP worker may omit SystemRoot from child-process env.
            // Python needs it to load Windows' cryptographic random provider.
            $systemRoot = config('attendance.system_root');
            if (!$systemRoot || !is_dir($systemRoot.DIRECTORY_SEPARATOR.'System32')) {
                throw new \RuntimeException('Konfigurasi Windows untuk mesin wajah belum valid. Hubungi pengelola.');
            }
            $environment = ['SystemRoot' => $systemRoot, 'SYSTEMROOT' => $systemRoot, 'WINDIR' => $systemRoot];
        }
        $process = new Process([
            config('attendance.python'), base_path('tools/face/recognize.py'),
            '--models', config('attendance.models'), '--image', $path,
        ], base_path(), $environment);
        $process->setTimeout(30);
        $process->run();
        if (!$process->isSuccessful()) {
            Log::error('attendance_face_engine_failed', ['exit_code' => $process->getExitCode(), 'error' => mb_substr($process->getErrorOutput(), 0, 3000)]);
            throw new \RuntimeException('Mesin verifikasi wajah belum siap. Hubungi pengelola.');
        }
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['error'])) {
            throw ValidationException::withMessages(['photo' => $result['error']]);
        }
        $vector = $result['embedding'] ?? [];
        if (count($vector) !== 128 || collect($vector)->contains(fn ($v) => !is_numeric($v) || !is_finite((float) $v))) {
            throw new \RuntimeException('Hasil verifikasi wajah tidak valid.');
        }
        return $vector;
    }

    public function similarity(array $reference, array $probe): float
    {
        if (count($reference) !== 128 || count($probe) !== 128) {
            throw new \RuntimeException('Data wajah perlu didaftarkan ulang.');
        }
        $dot = $a = $b = 0;
        foreach ($reference as $i => $value) {
            $dot += $value * $probe[$i];
            $a += $value ** 2;
            $b += $probe[$i] ** 2;
        }
        return $a > 0 && $b > 0 ? $dot / sqrt($a * $b) : 0;
    }
}
