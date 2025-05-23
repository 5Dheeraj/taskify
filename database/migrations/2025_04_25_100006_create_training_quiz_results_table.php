<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_quiz_results', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedInteger('score')->default(0); // total marks obtained
            $table->unsignedInteger('total_questions')->default(0);

            $table->boolean('passed')->default(false);
            $table->unsignedInteger('attempt_count')->default(1); // attempt no.

            $table->timestamp('attempted_at')->nullable();

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['training_id', 'user_id']); // each user one result per training
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_quiz_results');
    }
};
