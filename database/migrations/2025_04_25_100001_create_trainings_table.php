<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('admin_id'); // Creator or Trainer
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('level', ['beginner', 'advanced'])->default('beginner');
            $table->enum('course_type', ['mandatory', 'optional'])->default('mandatory');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('language')->default('Hindi');
            $table->enum('status', ['draft', 'active', 'upcoming'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('completion_deadline')->nullable();
            $table->boolean('drip_enabled')->default(false); // For drip content control

            // 🔹 NEW FIELDS for assignment
            $table->json('assigned_roles')->nullable(); // Array of role_ids
            $table->json('assigned_users')->nullable(); // Array of user_ids

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
