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
                $table->string('theme_primary_color', 7)->nullable()->default('#0d6efd');
                $table->string('theme_secondary_color', 7)->nullable()->default('#6c757d');
                $table->string('theme_background_color', 7)->nullable()->default('#ffffff');
                $table->string('theme_text_color', 7)->nullable()->default('#212529');
                $table->string('theme_font', 30)->nullable()->default('default');
                $table->boolean('is_custom_theme')->nullable()->default(false);
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
            $table->dropColumn('theme_primary_color');
            $table->dropColumn('theme_secondary_color');
            $table->dropColumn('theme_background_color');
            $table->dropColumn('theme_text_color');
            $table->dropColumn('theme_font');
            $table->dropColumn('is_custom_theme');
        });
    }
};
