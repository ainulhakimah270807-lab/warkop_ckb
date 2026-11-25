<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AttendanceAdmin extends Command
{
    protected $signature = 'presensi:admin {email} {--revoke}';
    protected $description = 'Tetapkan atau cabut akses pengelola presensi untuk akun yang sudah ada';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('Email belum terdaftar di Warkop CKB.');
            return self::FAILURE;
        }
        $user->is_attendance_admin = !$this->option('revoke');
        $user->role = $this->option('revoke') ? 'karyawan' : 'owner';
        $user->save();
        $this->info('Hak pengelola presensi diperbarui untuk '.$user->email);
        return self::SUCCESS;
    }
}
