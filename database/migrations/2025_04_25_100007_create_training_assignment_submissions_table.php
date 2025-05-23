<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('user_id');

            $table->string('file_path')->nullable(); // uploaded file path
            $table->text('text_response')->nullable(); // optional text answer

            $table->enum('status', ['pending', 'submitted', 'reviewed'])->default('pending');
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['training_id', 'user_id']); // 1 assignment per user per training
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignment_submissions');
    }
};
