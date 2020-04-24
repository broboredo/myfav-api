<?php

namespace App\Http\Controllers;

use App\Models\Appearance;
use App\Models\Character;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use mysql_xdevapi\Exception;

class Api extends Controller
{
    public function getNext(Request $request)
    {
        $chosenCharacters = Character::with('sitcom')->inRandomOrder()->limit(2)->get();

        $appearance = $this->addAppearance($chosenCharacters);

        return new JsonResponse([
            'chosenCharacters' => $chosenCharacters,
            'appearanceId' => $appearance->id
        ]);
    }

    public function vote(Request $request)
    {
        try {
            $vote = new Vote();
            $vote->character_id = $request->character_id;
            $vote->appearance_id = $request->appearance_id;
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

    public function ranking(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;
        return Character::ranking()->limit($limit)->get();
    }

    public function allCharacters()
    {
        return Character::all(['name', 'id']);
    }

    public function appearances(Request $request)
    {
        $characterOne = $request->character_one;
        $characterTwo = $request->character_two;

        $appearances = Appearance::where(function($q) use ($characterOne, $characterTwo) {
            $q->where('character_one', $characterOne)
                ->where('character_two', $characterTwo);
        })
        ->orWhere(function($q) use ($characterOne, $characterTwo) {
            $q->where('character_one', $characterTwo)
                ->where('character_two', $characterOne);
        })
        ->get();

        $votes = Vote::with([
            'character'
        ])
        ->select([
            'votes.character_id',
            \DB::raw('count(*) as votes_count')
        ])
        ->rightJoin('appearances', function ($query) use ($characterOne, $characterTwo) {
            $query->on('appearances.id', '=', 'votes.appearance_id');
        })
        ->where(function ($q) use ($characterOne, $characterTwo) {
            $q->where('character_one', $characterOne)
                ->where('character_two', $characterTwo);
        })
        ->orWhere(function ($q) use ($characterOne, $characterTwo) {
            $q->where('character_one', $characterTwo)
                ->where('character_two', $characterOne);
        })
        ->groupBy('votes.character_id')
        ->get();

        $sum = 0;
        $votes->each(function($vote) use(&$sum) {
            $sum += $vote->votes_count;
        });

        if($sum !== $appearances->count()) {
            throw new \Exception('error_sum', 422);
        }


        return new JsonResponse([
            'count' => $appearances->count(),
            'votes' => $votes
        ]);
    }

    private function addAppearance($chosenCharacters): Appearance
    {
        $characterOne = $chosenCharacters->first();
        $characterTwo = $chosenCharacters->get(1); //get second character

        $appearance = new Appearance();
        $appearance->character_one = $characterOne->id;
        $appearance->character_two = $characterTwo->id;
        $appearance->save();

        return $appearance;
    }
}
