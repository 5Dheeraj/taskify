<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_type', ['video', 'document'])->default('video');
            $table->string('media_url'); // YouTube/Vimeo link or PDF path
            $table->integer('order')->default(0); // Lesson order
            $table->boolean('is_locked')->default(false); // Unlock on previous complete
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_contents');
    }
};
