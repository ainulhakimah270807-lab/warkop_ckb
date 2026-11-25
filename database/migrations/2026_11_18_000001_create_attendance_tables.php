<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_attendance_admin')->default(false);
        });
        Schema::create('attendance_outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('latitude');
            $table->double('longitude');
            $table->unsignedInteger('radius_meters');
            $table->timestamps();
        });
        Schema::create('attendance_faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('template');
            $table->foreignId('enrolled_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_outlet_id')->constrained();
            $table->uuid('request_id')->unique();
            $table->date('work_date');
            $table->string('type', 10);
            $table->timestamp('recorded_at');
            $table->double('latitude');
            $table->double('longitude');
            $table->double('accuracy');
            $table->double('distance_meters');
            $table->double('face_score');
            $table->string('photo_path');
            $table->timestamps();
            $table->unique(['user_id', 'work_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_faces');
        Schema::dropIfExists('attendance_outlets');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_attendance_admin'));
    }
};
