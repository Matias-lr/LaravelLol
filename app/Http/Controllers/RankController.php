<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class RankController extends Controller
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
                return $res->getStatusCode();
            }else{
                return $res->getStatusCode();
            }
        }catch (Exception $e){
            return $e;
        }
    }
        
    public function GetRank(){
        $servers = collect(json_decode(file_get_contents('jsons/estaticos/servers.json')));
        $featuredGame = collect($this->sendApiRequestWithEx('la2','/lol/spectator/v4/featured-games','?'));
        return $featuredGame;
    }
}
