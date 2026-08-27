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
        if (version_compare(app()->version(), '11', '>=')) {
            if (Schema::hasColumn('users', 'password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('password')->nullable()->change();
                });
            } else {
                $output = new ConsoleOutput();
                $output->writeln('The users table does not have the password column. Skipping making password nullable.');
            } //End if
        } else {
            throw new \LogicException('Laravel version is not supported. Works only with Laravel 11 or higher.');
        } //End if
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable(false)->change();
            });
        } else {
            $output = new ConsoleOutput();
            $output->writeln('The users table does not have the password column. Skipping making password not nullable.');
        } //End if
    }
};
