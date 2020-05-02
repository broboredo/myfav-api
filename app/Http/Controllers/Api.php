<?php

namespace App\Http\Controllers;

use App\Models\Appearance;
use App\Models\Character;
use App\Models\Sitcom;
use App\Models\Vote;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
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
            \DB::raw('count(votes.character_id) as votes_count'),
            \DB::raw('count(appearances.id) as total_appearances'),
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
        ->orderBy('total_appearances', 'asc')
        ->get();

        $totalVotes = 0;
        $isDraw = false;
        $characters->each(function ($vote, $key) use (&$totalVotes, &$isDraw) {
            if($key === 1 && $totalVotes === $vote->votes_count) {
                $isDraw = true;
            }

            $totalVotes += $vote->votes_count;
        });

        $skips = $appearancesCount - $totalVotes;

        return new JsonResponse([
            'count' => $appearancesCount,
            'characters' => $characters,
            'skips' => $skips,
            'draw' => $isDraw
        ]);
    }

    public function storeSitcom(Request $request)
    {
        try {
            if($request->token !== env('TOKEN_STORE_SITCOM')) {
                throw new Exception([
                    'error'
                ]);
            }

            if(!is_array($request->name)) {
                $newSitcoms[] = $request->name;
            } else {
                $newSitcoms = $request->name;
            }

            foreach ($newSitcoms as $newSitcom) {
                $sitcom = new Sitcom();
                $sitcom->name = $newSitcom;
                $sitcom->save();
            }

            return new JsonResponse([
                'message' => 'Added'
            ], 200);

        } catch(\Exception $e) {
            return new JsonResponse([
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function runCommand(Request $request)
    {
        try {
            if($request->token !== env('TOKEN_STORE_SITCOM')) {
                throw new Exception([
                    'error'
                ]);
            }

            Artisan::command($request->command, function ($project) {
                //
            });

            return new JsonResponse([
                'message' => 'Running'
            ], 200);

        } catch(\Exception $e) {
            return new JsonResponse([
                'msg' => $e->getMessage()
            ]);
        }
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

    public function test($search = 'house of cards annette shepherd')
    {

            $client = new Client();
            $response = $client->get('https://api.qwant.com/api/search/images?' .
                'uiv=4&t=images&safesearch=1&locale=en_US&q=' . $search, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception([
                    'search' => $search,
                    'code' => $response->getStatusCode()
                ]);
            }

            /* @var Collection $results */
            $results = collect(json_decode($response->getBody()->getContents())->data->result->items);

            $result = $results->where('height', '>=', '250');
            $result = $result->each(function ($r, $key) use ($result) {
                if($r->width <= $r->height*1.1) {
                    //
                } else {
                    $result->forget($key);
                }
            });


            if ($result->count() > 0) {
                return $result->get(0)->media;
            }

            return '';
    }
}
