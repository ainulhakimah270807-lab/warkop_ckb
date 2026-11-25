<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class AttendanceLocation
{
    public function validate(object $outlet, float $latitude, float $longitude, float $accuracy): float
    {
        if ($accuracy <= 0 || $accuracy > config('attendance.max_accuracy')) {
            throw ValidationException::withMessages(['accuracy' => 'GPS belum akurat. Pindah ke area terbuka lalu coba lagi.']);
        }
        $lat = deg2rad($latitude - $outlet->latitude);
        $lon = deg2rad($longitude - $outlet->longitude);
        $a = sin($lat / 2) ** 2 + cos(deg2rad($outlet->latitude)) * cos(deg2rad($latitude)) * sin($lon / 2) ** 2;
        $distance = 6371000 * 2 * asin(sqrt(min(1, max(0, $a))));
        if ($distance + $accuracy > $outlet->radius_meters) {
            throw ValidationException::withMessages(['location' => 'Anda berada di luar radius outlet atau akurasi GPS melewati batas radius.']);
        }
        return $distance;
    }
}
