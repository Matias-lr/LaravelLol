<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class MasterAppController extends Controller
{
    private function sendApiRequestWithEx($region,$path,$requestData){
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://'.$region.env('API_URL'),
        ]);
        $key = 'api_key='.env('API_KEY');
        try{
            $res = $client->request('GET', $path.$requestData.$key,['exceptions' => false]);
            if($res->getStatusCode() == 200){
                return json_decode($res->getBody(), true);
            }else if($res->getStatusCode() == 404){
                return 0;
            }else{
                return 1;
            }
        }catch (Exception $e){
            return $e;
        }
    }
    public function GetLeague($idioma,$region,$summoner){
        $servers = json_decode(file_get_contents('jsons/estaticos/servers.json'));
        $region = collect(collect($servers)['regions'])->where('region','=',$region)->first()->id;
        $summoner = $this->sendApiRequestWithEx($region,'/lol/summoner/v4/summoners/by-name/',$summoner.'?');
        $summonerId = $summoner['id'];
        $summonerRank = $this->sendApiRequestWithEx($region,'/lol/league/v4/entries/by-summoner/',$summonerId.'?');
        $jsonRank = [];
        foreach($summonerRank as $rank){
            if(isset($rank['miniSeries'])){
                $jsonSeries =  $rank['miniSeries']['progress'];
            }else{
                $jsonSeries = 0;
            }
            array_push($jsonRank,array(
                "tier" => $rank['tier'],
                "range" => $rank['rank'],
                "cola" => $rank['queueType'],
                "rango" => $rank['rank'],
                "points" => $rank['leaguePoints'],
                "wins" => $rank['wins'],
                "losses" => $rank['losses'],
                "serie" => $jsonSeries
            ));
        }
        return $jsonRank;
    }
}
