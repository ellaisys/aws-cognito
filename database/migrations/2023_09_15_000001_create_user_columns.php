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
            if (!Schema::hasColumn('users', 'sub')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('sub')->nullable()->index();
                });                
            } else {
                $output = new ConsoleOutput();
                $output->writeln('The users table has the sub column. Skipping adding sub column.');
            } //End if
        } else {
            throw new \Exception('Laravel version is not supported. Works only with Laravel 8.37 or higher.');
        } //End if
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'sub')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'sub',
                ]);
            });
        } else {
            $output = new ConsoleOutput();
            $output->writeln('The users table does not have the sub column. Skipping dropping sub column.');
        } //End if
    }
};
