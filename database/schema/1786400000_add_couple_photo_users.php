<?php

use Core\Database\Migration;
use Core\Database\Schema;
use Core\Database\Table;

return new class implements Migration
{
    /**
     * One photo of the couple, held in the database rather than object storage.
     *
     * That is deliberate and only reasonable at this size: it is a single image,
     * changed rarely, resized in the browser to roughly 150-250 kB before it is
     * ever sent. A gallery of many photos would not belong here.
     *
     * The bytes are base64 because the schema builder offers no binary column;
     * that costs about a third in size and nothing in correctness.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Table $table) {
            $table->addColumn(function (Table $table) {
                $table->text('photo_couple')->nullable();
                $table->string('photo_couple_type', 30)->nullable();

                // Short content hash. It is what makes the public photo URL
                // change when the picture does, so the year-long cache header
                // is safe to send.
                $table->string('photo_couple_version', 32)->nullable();
            });
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Table $table) {
            $table->dropColumn('photo_couple');
            $table->dropColumn('photo_couple_type');
            $table->dropColumn('photo_couple_version');
        });
    }
};
