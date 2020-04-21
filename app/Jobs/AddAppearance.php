<?php

namespace App\Jobs;

use App\Models\Appearance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddAppearance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $characterOne;
    protected $characterTwo;

    /**
     * Create a new job instance.
     *
     * @param $chosenCharacters
     * @return void
     */
    public function __construct($chosenCharacters)
    {
        $this->characterOne = $chosenCharacters->first();
        $this->characterTwo = $chosenCharacters->get(1); //get second character
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger('In', []);
        $appearance = new Appearance();
        $appearance->character_one = $this->characterOne->id;
        $appearance->character_two = $this->characterTwo->id;
        $appearance->save();
    }
}
