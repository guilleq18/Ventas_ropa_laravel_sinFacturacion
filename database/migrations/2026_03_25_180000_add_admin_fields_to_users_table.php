<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 80)->nullable()->unique()->after('name');
            $table->string('first_name', 80)->default('')->after('username');
            $table->string('last_name', 80)->default('')->after('first_name');
            $table->boolean('is_active')->default(true)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'first_name', 'last_name', 'is_active']);
        });
    }
};
