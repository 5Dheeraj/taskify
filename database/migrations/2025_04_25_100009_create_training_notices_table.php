<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_notices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('posted_by'); // admin or instructor user id
            $table->string('title');
            $table->text('message');
            $table->enum('audience', ['all', 'assigned_users'])->default('assigned_users'); // कौन देख सकता है
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_notices');
    }
};
