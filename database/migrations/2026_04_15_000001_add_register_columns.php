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
            if (!Schema::hasColumn('users', 'register_type')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('register_type')->nullable();
                    $table->timestamp('registered_at')->nullable();
                });
            } else {
                $output = new ConsoleOutput();
                $output->writeln('The users table has the register_type column. Skipping adding register_type column.');
            } //End if
        } else {
            throw new \RuntimeException('Laravel version is not supported. Works only with Laravel 8.37 or higher.');
        } //End if
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'register_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'register_type',
                    'registered_at',
                ]);
            });
        } else {
            $output = new ConsoleOutput();
            $output->writeln('The users table does not have the register_type column. Skipping dropping register_type column.');
        } //End if
    }
};
