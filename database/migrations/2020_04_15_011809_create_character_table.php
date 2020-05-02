<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharacterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('img');
            $table->integer('gender')->nullable();
            $table->foreignId('sitcom_id');
            $table->timestamps();
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->foreign('sitcom_id', 'fk_character_sitcom')->references('id')->on('sitcoms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign('fk_character_sitcom');
        });

        Schema::dropIfExists('characters');
    }
}
