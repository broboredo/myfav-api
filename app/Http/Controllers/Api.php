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

        $appearances = Appearance::where(function ($query) use ($characterOne, $characterTwo) {
            $query->where('character_one', $characterOne)
                ->where('character_two', $characterTwo);
        })
            ->orWhere(function ($query) use ($characterOne, $characterTwo) {
                $query->where('character_one', $characterTwo)
                    ->where('character_two', $characterOne);
            })->get();

        $appearancesCount = $appearances->count();

        $characters = Character::with([
            'sitcom'
        ])
        ->select([
            'characters.*',
            \DB::raw('count(votes.character_id) as votes_count')
        ])
        ->leftJoin('appearances', function ($query) {
            $query->on('appearances.character_one', 'characters.id')
                ->orOn('appearances.character_two', 'characters.id');
        })
        ->leftJoin('votes', function ($query) use ($characterOne, $characterTwo) {
            $query->on('votes.appearance_id', 'appearances.id')
                ->on('votes.character_id', 'characters.id')
                ->on(function ($subQuery) use ($characterOne, $characterTwo) {
                    $subQuery->where(function ($q) use ($characterOne, $characterTwo) {
                        $q->where('appearances.character_one', $characterOne)
                            ->where('appearances.character_two', $characterTwo);
                    })
                        ->orWhere(function ($q) use ($characterOne, $characterTwo) {
                            $q->where('appearances.character_one', $characterTwo)
                                ->where('appearances.character_two', $characterOne);
                        });
                });
        })
        ->whereIn('characters.id', [
            $characterOne,
            $characterTwo
        ])
        ->groupBy('characters.id')
        ->orderBy('votes_count', 'desc')
        ->get();

        $totalVotes = 0;
        $characters->each(function ($vote) use (&$totalVotes) {
            $totalVotes += $vote->votes_count;
        });

        $skips = $appearancesCount - $totalVotes;

        return new JsonResponse([
            'count' => $appearancesCount,
            'characters' => $characters,
            'skips' => $skips
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
