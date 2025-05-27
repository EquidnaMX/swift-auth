<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Roles', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('actions');
            $table->timestamps();
        });

        Schema::create('UsersRoles', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_role');
            $table->primary(['id_user', 'id_role']);
            $table->foreign('id_user')->references('id_user')->on('Users')->onDelete('cascade');
            $table->foreign('id_role')->references('id_role')->on('Roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('UsersRoles');
        Schema::dropIfExists('Roles');
    }
};
