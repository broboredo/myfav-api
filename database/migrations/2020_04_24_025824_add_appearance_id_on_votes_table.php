<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppearanceIdOnVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('votes', function(Blueprint $table) {
            $table->foreignId('appearance_id');
        });

        Schema::table('votes', function(Blueprint $table) {
            $table->foreign('appearance_id', 'fk_votes_appearances')
                ->on('appearances')
                ->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('votes', function(Blueprint $table) {
            $table->dropForeign('fk_votes_appearances');
        });

        Schema::table('votes', function(Blueprint $table) {
            $table->dropColumn('appearance_id');
        });
    }
}
