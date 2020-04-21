<?php

namespace App\Http\Controllers;

use App\Jobs\AddAppearance;
use App\Models\Character;
use App\Models\Sitcom;
use App\Models\Vote;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class Api extends Controller
{
    public function getNext(Request $request)
    {
        $chosenCharacters = Character::with('sitcom')->inRandomOrder()->limit(2)->get();

        AddAppearance::dispatch($chosenCharacters);

        return $chosenCharacters;
    }

    public function vote(Request $request)
    {
        try {
            $vote = new Vote();
            $vote->character_id = $request->character_id;
            $vote->save();

            return new JsonResponse(
                'success'
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                'error'
            );
        }
    }
}
