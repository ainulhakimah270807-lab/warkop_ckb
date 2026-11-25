<?php

return [
    'timezone' => 'Asia/Jakarta',
    'python' => env('ATTENDANCE_PYTHON', base_path('tools/face/.venv/Scripts/python.exe')),
    'models' => env('ATTENDANCE_MODELS', storage_path('app/private/face-models')),
    'system_root' => env('ATTENDANCE_SYSTEM_ROOT', getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: getenv('WINDIR') ?: ((getenv('SystemDrive') ?: 'C:').'\\Windows')),
    'face_threshold' => (float) env('ATTENDANCE_FACE_THRESHOLD', 0.45),
    'max_accuracy' => 50,
];
