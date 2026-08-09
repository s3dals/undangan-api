<?php

use Core\Database\Migration;
use Core\Database\Schema;
use Core\Database\Table;

return new class implements Migration
{
    /**
     * Jalankan migrasi.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Table $table) {
            $table->addColumn(function (Table $table) {
                // Stored as YYYY-MM-DD. Kept as a plain date (no time) because it
                // means "the whole of this day", resolved in the owner's timezone.
                $table->string('rsvp_deadline', 10)->nullable();
            });
        });
    }

    /**
     * Kembalikan seperti semula.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Table $table) {
            $table->dropColumn('rsvp_deadline');
        });
    }
};
