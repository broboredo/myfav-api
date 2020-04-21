<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppearancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_one');
            $table->foreignId('character_two');
            $table->timestamps();
        });

        Schema::table('appearances', function (Blueprint $table) {
            $table->foreign('character_one', 'fk_appearances_character_one')
                ->references('id')
                ->on('characters') ;

            $table->foreign('character_two', 'fk_appearances_character_two')
                ->references('id')
                ->on('characters') ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appearances', function (Blueprint $table) {
           $table->dropForeign('fk_appearances_character_one') ;
            $table->dropForeign('fk_appearances_character_two') ;
        });

        Schema::dropIfExists('appearances');
    }
}
