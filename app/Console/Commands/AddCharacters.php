<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\Sitcom;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use mysql_xdevapi\Exception;

class AddCharacters extends Command
{
    private $client;
    private $baseUrl = 'https://api.themoviedb.org/3/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:add:characters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new Characters';

    /**
     * Create a new command instance.
     *
     * @param Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /* @var Collection $sitcomsToUpdate */
        $sitcomsToUpdate = Sitcom::whereNotNull('external_id')
        ->doesntHave('characters')
        ->limit(10);

        $sitcomsToUpdate->each(function (Sitcom $sitcom) {
            $characters = $this->search($sitcom);
            $this->addCharacters($sitcom, $characters);
        });
    }

    private function search(Sitcom $sitcom)
    {
        try {
            $externalId = $sitcom->external_id;
            $numberOfSeasons = $sitcom->number_of_seasons;
            $cast = collect();

            for ($seasonIndex = 1; $seasonIndex <= $numberOfSeasons; $seasonIndex++) {
                $response = $this->client->get($this->baseUrl .
                    'tv/' . $externalId . '/season/' . $seasonIndex . '/credits', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('TOKEN_TMDB')
                    ]
                ]);

                if ($response->getStatusCode() !== 200) {
                    logger()->error('COMMAND_ERROR', [
                        'sitcom_external_id' => $externalId,
                        'season_index' => $seasonIndex
                    ]);
                    continue;
                }

                /* @var Collection $results */
                $results = collect(json_decode($response->getBody()->getContents())->cast);
                $results->each(function ($character) use (&$cast) {
                    if (!$cast->contains('character', $character->character)) {
                        $cast->add($character);
                    }
                });
            }

            if ($cast->count() <= 0) {
                throw new Exception([
                    'message' => 'not found',
                    'status' => 404
                ]);
            }

            return $cast;
        } catch (\Exception $e) {
            logger()->error('SEARCH_CHAR_ERROR', [
                'sitcom_id' => $sitcom->id,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function addCharacters(Sitcom $sitcom, Collection $characters)
    {
        $characters->each(function ($characterFromCast) use($sitcom) {
            try {
                $image = $this->getImage($sitcom->name . ' ' . $characterFromCast->character);

                $character = new Character();
                $character->name = $characterFromCast->character;
                $character->img =
                    $image !== '' ? $image
                    : 'https://image.tmdb.org/t/p/w500' . $characterFromCast->profile_path;
                $character->gender = $characterFromCast->gender;
                $character->sitcom_id = $sitcom->id;

                $character->save();
            } catch (\Exception $e) {
                logger()->error('ADD_CHARACTER_ERROR', [
                    'sitcom_id' => $sitcom->id,
                    'character_name' => $characterFromCast->character,
                    'message' => $e->getMessage()
                ]);
            }
        });
    }

    private function getImage($search)
    {
        try {
            $response = $this->client->get('https://api.qwant.com/api/search/images?' .
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
        } catch (\Exception $e) {
            logger()->error('ERROR_SEARCH_IMAGE', [
                'search' => $search,
                'message' => $e->getMessage()
            ]);
        }
    }
}
