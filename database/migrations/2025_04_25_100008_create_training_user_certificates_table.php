<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_user_certificates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('user_id');

            $table->string('certificate_file')->nullable(); // PDF file path
            $table->timestamp('issued_at')->nullable(); // कब issue हुआ
            $table->boolean('quiz_passed')->default(false); // अगर quiz अनिवार्य हो

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['training_id', 'user_id']); // एक ही user को दो बार certificate न मिले
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_user_certificates');
    }
};
