<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_id');

            $table->text('question');
            $table->enum('type', ['mcq', 'true_false'])->default('mcq');

            // Only for MCQ (comma-separated options)
            $table->text('options')->nullable(); // e.g., Option A,Option B,Option C

            // Correct answer
            $table->string('correct_answer'); // For MCQ: 'Option B', for True/False: 'true'

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_quizzes');
    }
};
