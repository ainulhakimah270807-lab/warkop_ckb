<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAttendanceOwner extends Command
{
    protected $signature = 'presensi:owner {email} {--name=Owner Warkop CKB}';
    protected $description = 'Buat akun Owner baru dengan kata sandi acak. Akun yang sudah ada tidak diubah.';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Email tidak valid atau sudah digunakan. Akun yang sudah ada tidak diubah.');
            return self::FAILURE;
        }
        $password = bin2hex(random_bytes(10)).'!Ckb';
        $user = new User();
        $user->name = $this->option('name');
        $user->email = $email;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->role = 'owner';
        $user->is_attendance_admin = true;
        $user->save();
        $this->info('Akun Owner berhasil dibuat. Simpan kata sandi ini.');
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);
        return self::SUCCESS;
    }
}
