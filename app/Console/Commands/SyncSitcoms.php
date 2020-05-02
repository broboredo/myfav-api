<?php

namespace App\Console\Commands;

use App\Models\Sitcom;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use mysql_xdevapi\Exception;

class SyncSitcoms extends Command
{
    private $client;
    private $baseUrl = 'https://api.themoviedb.org/3/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:sync:sitcoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Sitcoms table';

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
        $sitcomsToUpdate = Sitcom::whereNull('external_id')->limit(10);

        $sitcomsToUpdate->each(function (Sitcom $sitcom) {
            $externalId = $this->search($sitcom->name);
            $sitcom->external_id = $externalId;

            $this->searchDetailsAndUpdate($sitcom);
        });
    }

    private function search($sitcomName)
    {
        $response = $this->client->get($this->baseUrl . 'search/tv?query='.$sitcomName, [
            'headers' => [
                'Authorization' => 'Bearer '.env('TOKEN_TMDB')
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            throw new Exception([
                'message' => 'error',
                'status' => $response->getStatusCode()
            ]);
        }

        /* @var Collection $results */
        $results = collect(json_decode($response->getBody()->getContents())->results);

        if(!$results) {
            throw new Exception([
                'message' => 'not found',
                'status' => 404
            ]);
        }

        return $results->first()->id;
    }

    private function searchDetailsAndUpdate(Sitcom $sitcom): Sitcom
    {
        $response = $this->client->get($this->baseUrl . 'tv/'.$sitcom->external_id, [
            'headers' => [
                'Authorization' => 'Bearer '.env('TOKEN_TMDB')
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            throw new Exception([
                'message' => 'error',
                'status' => $response->getStatusCode()
            ]);
        }

        /* @var Collection $results */
        $results = json_decode($response->getBody()->getContents());

        if(!$results) {
            throw new Exception([
                'message' => 'not found',
                'status' => 404
            ]);
        }

        $sitcom->start_date = $results->first_air_date;
        $sitcom->logo = $results->poster_path;
        $sitcom->number_of_seasons = $results->number_of_seasons;
        if(is_null($results->next_episode_to_air) && !is_null($results->last_air_date)) {
            $sitcom->end_date = $results->last_air_date;
        }

        $sitcom->save();

        return $sitcom;
    }
}
