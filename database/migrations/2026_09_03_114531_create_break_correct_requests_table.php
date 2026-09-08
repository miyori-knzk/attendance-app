<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('break_correct_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_correct_request_id');
            $table->string('new_break_in', 5);
            $table->string('new_break_out', 5);
            $table->timestamps();
            $table->foreign('attendance_correct_request_id', 'fk_attendance_correct_request2')
                ->references('id')
                ->on('attendance_correct_requests')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_correct_requests');
    }
};
