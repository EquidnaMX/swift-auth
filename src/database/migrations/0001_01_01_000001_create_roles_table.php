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
        $prefix = config('swift-auth.table_prefix', '');

        Schema::create($prefix . 'Roles', function (Blueprint $table) {
            $table->id('id_role');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('actions');
            $table->timestamps();
        });
        Schema::create($prefix . 'UsersRoles', function (Blueprint $table) use ($prefix) {
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_role');
            $table->primary(['id_user', 'id_role']);
            $table->foreign('id_user')->references('id_user')->on($prefix . 'Users')->onDelete('cascade');
            $table->foreign('id_role')->references('id_role')->on($prefix . 'Roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('swift-auth.table_prefix', '');
        Schema::dropIfExists($prefix . 'UsersRoles');
        Schema::dropIfExists($prefix . 'Roles');
    }
};
