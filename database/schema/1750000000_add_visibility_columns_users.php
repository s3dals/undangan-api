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
                $table->boolean('show_home')->nullable()->default(true);
                $table->boolean('show_bride')->nullable()->default(true);
                $table->boolean('show_wedding_date')->nullable()->default(true);
                $table->boolean('show_gallery')->nullable()->default(true);
                $table->boolean('show_comment')->nullable()->default(true);
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
            $table->dropColumn('show_home');
            $table->dropColumn('show_bride');
            $table->dropColumn('show_wedding_date');
            $table->dropColumn('show_gallery');
            $table->dropColumn('show_comment');
        });
    }
};
