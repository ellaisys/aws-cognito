<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (version_compare(app()->version(), '8.37', '>=')) {
            if (!Schema::hasColumn('users', 'is_webauthn_enabled')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->boolean('is_webauthn_enabled')->default(false);
                });
            } else {
                $output = new ConsoleOutput();
                $output->writeln('The users table has the is_webauthn_enabled column. Skipping adding is_webauthn_enabled column.');
            } //End if
        } else {
            throw new \LogicException('Laravel version is not supported. Works only with Laravel 8.37 or higher.');
        } //End if
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_webauthn_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'is_webauthn_enabled',
                ]);
            });
        } else {
            $output = new ConsoleOutput();
            $output->writeln('The users table does not have the is_webauthn_enabled column. Skipping dropping is_webauthn_enabled column.');
        } //End if
    }
};
