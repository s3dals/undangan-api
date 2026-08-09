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
        // Key/value rather than a column per string: the invitation has dozens of
        // editable texts and new ones should not each need a migration.
        Schema::create('contents', function (Table $table) {
            $table->id();

            $table->integer('user_id');
            // "key"/"value" are reserved words in MySQL, hence the prefix.
            $table->string('content_key', 50);
            $table->text('content_value')->nullable();

            // No explicit index: the table holds a few dozen rows per user and is
            // always read by user_id, which the foreign key already covers.
            // (Table::index() emits MySQL-only "KEY ..." syntax and fails on pgsql.)
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
        Schema::drop('contents');
    }
};
