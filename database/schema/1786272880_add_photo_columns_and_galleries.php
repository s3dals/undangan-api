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
                $table->text('photo_home_url')->nullable();
                $table->text('photo_bride_url')->nullable();
                $table->text('photo_groom_url')->nullable();
            });
        });

        Schema::create('galleries', function (Table $table) {
            $table->id();

            $table->integer('user_id');
            $table->text('url');
            $table->text('storage_path');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->timeStamp();
        });
    }

    /**
     * Kembalikan seperti semula.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('galleries');

        Schema::table('users', function (Table $table) {
            $table->dropColumn('photo_home_url');
            $table->dropColumn('photo_bride_url');
            $table->dropColumn('photo_groom_url');
        });
    }
};
