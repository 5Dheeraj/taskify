<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingProgressesTable extends Migration
{
    public function up()
    {
        Schema::create('training_progresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('content_id');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_progresses');
    }
}
