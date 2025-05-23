<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_slides', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('admin_id');

            $table->string('title');
            $table->text('description')->nullable(); // Slide text
            $table->enum('content_type', ['video', 'text', 'mixed'])->default('text');
            $table->string('media_url')->nullable(); // video link or ppt
            $table->integer('order')->default(1);
            $table->string('duration')->nullable(); // optional

            $table->boolean('drip_enabled')->default(false);
            $table->date('visible_after')->nullable();

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_slides');
    }
};
