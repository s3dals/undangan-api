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
                // theme_font stays the Latin face. Arabic needs its own, because
                // none of the Latin families carry Arabic glyphs - an invitation
                // written in Arabic was falling back to whatever the device chose.
                $table->string('theme_font_arabic', 30)->nullable()->default('default');

                // auto | ltr | rtl. "auto" decides from the stored content.
                $table->string('theme_direction', 4)->nullable()->default('auto');

                // Null means "follow the text colour", which is how the dividers
                // have always been painted.
                $table->string('theme_divider_color', 7)->nullable();
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
            $table->dropColumn('theme_font_arabic');
            $table->dropColumn('theme_direction');
            $table->dropColumn('theme_divider_color');
        });
    }
};
