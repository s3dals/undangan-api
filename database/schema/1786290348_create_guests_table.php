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
        Schema::create('guests', function (Table $table) {
            $table->id();

            $table->integer('user_id');
            $table->string('name', 100);
            $table->string('token', 32)->unique();
            $table->integer('max_guests')->default(1);

            // pending | attending | declined
            $table->string('status', 10)->default('pending');
            $table->integer('guest_count')->default(0);
            $table->dateTime('responded_at')->nullable();

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
        Schema::drop('guests');
    }
};
