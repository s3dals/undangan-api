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
        Schema::table('guests', function (Table $table) {
            $table->addColumn(function (Table $table) {
                // Per-guest wording above the name ("Dear Mr.", "To the family of").
                // Null falls back to the invitation-wide welcome message.
                $table->string('greeting', 100)->nullable();
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
        Schema::table('guests', function (Table $table) {
            $table->dropColumn('greeting');
        });
    }
};
