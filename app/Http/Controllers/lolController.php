<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class lolController extends Controller
{
    private function executeApis(){
        
    }
    //get summoner data
    private function sendApiRequest($region,$path,$requestData){
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://'.$region.env('API_URL'),
        ]);
        $key = '?api_key='.env('API_KEY');
        try{
            $res = $client->request('GET', $path.$requestData.$key);
            return json_decode($res->getBody(), true);
        }catch (Exception $e){
            return $e;
        }
    }
    private function sendApiDataRequest($path){
        $client = new Client([
            'base_uri' => env('api_data_url'),
        ]);
        try{
            $res = $client->request('GET',$path);
            return json_decode($res->getBody(), true);
        }catch (Exception $e){
            return $e;
        }
    }
    private function getSummonerData($region,$sumonerName){
        return $this->sendApiRequest($region,env('SUMMONERS_PATH'),$sumonerName);
    }
    //get match history
    private function getMatchHistoryIds($region,$accountId){
        $responseData = $this->sendApiRequest($region,env('MATCH_HISTORY_PATH'),$accountId);
        return $responseData['matches'];
    }
    //build mathc history
    private function pullSummonersInfo($gameDetails){
        $summonersInfo = [];

        foreach($gameDetails['participantIdentities'] as $participant){
            $summonersInfo[$participant['participantId']] = $participant['player']['summonerName'];
        }
        return $summonersInfo;
    }
    private function findChampion($championId,$champions){
        $champion;
        $champion = collect($champions['data'])->where('key','=',$championId)->first();
        $champion = $champion['name'];

        return $champion;
    }
    private function findItem($itemId,$items){
        if($itemId != 0){
            $itemData = $items["data"][$itemId];
            if(gettype($itemData) == 'string'){
                $item = gettype($itemData);
            }else{
                $item = gettype($itemData);
            }
        }else{
            $item = 'f';
        }
        return $item;
    }
    private function getItemImage($itemId){
        if($itemId != 0){
            return 'http://ddragon.leagueoflegends.com/cdn/10.3.1/img/item/'.$itemId.'.png';
        }else{
            return 'empty slot';
        }
    }
    private function buildSummonerItemBuild($playerData,$items){
        $json = array(
            "item0" => $this->findItem($playerData["item0"],$items),
            "item0Image" => $this->getItemImage($playerData["item0"]),
            "item1" => $this->findItem($playerData["item1"],$items),
            "item1Image" => $this->getItemImage($playerData["item1"]),
            "item2" => $this->findItem($playerData["item2"],$items),
            "item2Image" => $this->getItemImage($playerData["item2"]),
            "item3" => $this->findItem($playerData["item3"],$items),
            "item3Image" => $this->getItemImage($playerData["item3"]),
            "item4" => $this->findItem($playerData["item4"],$items),
            "item4Image" => $this->getItemImage($playerData["item4"]),
            "item5" => $this->findItem($playerData["item5"],$items),
            "item5Image" => $this->getItemImage($playerData["item5"]),
            "item6" => $this->findItem($playerData["item6"],$items),
            "item6Image" => $this->getItemImage($playerData["item6"]),
        );
        return $json;
    }
    private function matchSummonerRuneStyle($runes, $perk){
        $runeStyle = collect($runes)->where("id",'=',$perk)->first();
        return $runeStyle ? $runeStyle["name"] : $runeStyle;
    }
    private function GetRuneStyleImage($runes,$perk){
        $runeStyle = collect($runes)->where("id",'=',$perk)->first();
        return $runeStyle ? $runeStyle["icon"] : $runeStyle;
    }
    private function SummonerSpell($spells,$spellId){
        foreach($spells['data'] as $spell){
            if($spell['key'] == $spellId){
                $summonerSpell = $spell["name"];
            };
        }
        return $summonerSpell;
    }
    private function GetSummonerSpellImage($spells,$spellId){
        foreach($spells['data'] as $spell){
            if($spell['key'] == $spellId){
                $summonerSpell = $spell["id"];
            };
        }
        return $summonerSpell;
    }
    private function buildSummonerBuild($playerData, $player,$items,$runes,$spells){
        $json = array(
            "items" => $this->buildSummonerItemBuild($playerData,$items),
            "runes" => array(
                "primary" => $this->matchSummonerRuneStyle($runes, $playerData["perkPrimaryStyle"]),
                "primaryImage" => 'https://ddragon.leagueoflegends.com/cdn/img/'.$this->GetRuneStyleImage($runes,$playerData["perkPrimaryStyle"]),
                "secondary" => $this->matchSummonerRuneStyle($runes, $playerData["perkSubStyle"]),
                "secondaryImage" => 'https://ddragon.leagueoflegends.com/cdn/img/'.$this->GetRuneStyleImage($runes,$playerData["perkSubStyle"]),
            ),
            "summonersSpells" => array(
                "D" => $this->SummonerSpell($spells,$player['spell1Id']),
                "DImage" => 'http://ddragon.leagueoflegends.com/cdn/10.3.1/img/spell/'.$this->GetSummonerSpellImage($spells,$player['spell1Id']).'.png',
                "f" => $this->SummonerSpell($spells,$player['spell2Id']),
                "FImage" => 'http://ddragon.leagueoflegends.com/cdn/10.3.1/img/spell/'.$this->GetSummonerSpellImage($spells,$player['spell2Id']).'.png'
            )
        );
        return $json;
    }
    private function calcKDA($playerData){
        $kills = $playerData['kills'];
        $deaths = $playerData['deaths'];
        $assists = $playerData["assists"];
        if($deaths != 0){
            return round((($kills + $assists)/$deaths) ,2);
        }else{
            return round(($kills + $assists) ,2);
        }
    }
    private function calcHighestMultiKill($playerData){
        $multiKills = array(
            0 => 'no mataste a nadie we',
            1 => "kill",
            2 => "Double",
            3 => "Triple",
            4 => "Quadra",
            5 => "Penta",
            6 => "Unreal"
        );
        $highestMultiKill = $multiKills[$playerData["largestMultiKill"]];
        return $highestMultiKill;
    }
    private function calcCreepScore($playerData){
        if(isset($playerData["neutralMinionsKilledTeamJungle"])){
            $creepScore = $playerData["totalMinionsKilled"] + $playerData["neutralMinionsKilled"] +$playerData["neutralMinionsKilledTeamJungle"] +$playerData["neutralMinionsKilledEnemyJungle"];
        }else{
            $creepScore = $playerData["totalMinionsKilled"];
        }
        
        return $creepScore;
    }
    //no descomentar hasta tener la api en produccion
    /*private function getSummonerIcon($summonerName){
        $summoner = $this->sendApiRequest(env('SUMMONERS_PATH'),$summonerName);
        return $summoner['profileIconId'];
    }*/
    private function buildSummonerStats($player, $playerId,$players,$champions,$items,$runes,$spells,$minutes){
        $playerData = $player['stats'];

        $chapmion = $this->findChampion($player["championId"],$champions);

        $json = array(
            "name" => $players[$playerId],
            //no descomentar hasta tener la api en produccion
            //"summonerIcon" => $this->getSummonerIcon($players[$playerId]),
            "team" => $player['teamId'],
            "campion" => $chapmion,
            "ChampionImage" => 'http://ddragon.leagueoflegends.com/cdn/10.3.1/img/champion/'.$chapmion.'.png',
            "role" => $player["timeline"]["role"].' '.$player["timeline"]["lane"],
            "build" => $this->buildSummonerBuild($playerData, $player,$items,$runes,$spells),
            "stats" => array(
                "win" => $playerData["win"],
                "championLevel" => $playerData["champLevel"],
                "score" => array(
                    "kills" => $playerData['kills'],
                    "deaths" => $playerData['deaths'],
                    "assists" => $playerData['assists'],
                    "kda" => $this->calcKDA($playerData),
                    "highestMultiKill" => $this->calcHighestMultiKill($playerData),
                    "visionScore" => $playerData["visionScore"],
                    "pinkWards" => $playerData["visionWardsBoughtInGame"],
                    "creepScore" => $this->calcCreepScore($playerData),
                    "creepScorePerMin" => round($this->calcCreepScore($playerData)/$minutes,0)
                )
            )
        );
        return $json;
    }
    private function pullSummonersData($idioma,$gameDetails,$players){
        $summonerStats = [];

        $version = $this->sendApiDataRequest("http://ddragon.leagueoflegends.com/api/versions.json");

        //http://ddragon.leagueoflegends.com/cdn/10.3.1/data/es_MX/champion.json
        $champions = json_decode(file_get_contents("jsons/".$idioma."/champions.json"),true);


        $items = json_decode(file_get_contents('jsons/'.$idioma."/items.json"),true);

        $runes = json_decode(file_get_contents('jsons/'.$idioma."/runesReforged.json"),true);

        $spells = $this->sendApiDataRequest("http://ddragon.leagueoflegends.com/cdn/".$version['0']."/data/es_MX/summoner.json");

        $init = $gameDetails["gameDuration"];
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        if(strlen($seconds) == 1){
            $seconds = '0'.$seconds;
        }

        foreach($gameDetails['participants'] as $participantes){
            $playerId = $participantes['participantId'];
            $summonerStats[$playerId] = $this->buildSummonerStats($participantes, $playerId, $players,$champions,$items,$runes,$spells,$minutes);
        }
        return $summonerStats;
    }
    private function buildMatch($queues,$responseData,$summonersData){
        $init = $responseData["gameDuration"];
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        if(strlen($seconds) == 1){
            $seconds = '0'.$seconds;
        }
        $gameDuration = $minutes.':'.$seconds;
        $gameTime = $responseData['gameDuration'];
        $winningTeam = $summonersData['1']['stats']['win'] ? "blue" : "Red";
        $match = array(
            "gameDuration" => $gameDuration,
            "gameTime" => $gameTime,
            "winningTeam" => $winningTeam,
            "queue" => collect($queues)->where('queueId','=',$responseData["queueId"])->first()->description,
            "map" => collect($queues)->where('queueId','=',$responseData["queueId"])->first()->map,
            "gameMode" => $responseData['gameMode'],
            "gameVersion" => $responseData['gameVersion'],
            "players" => $summonersData 
        );
        return $match;
    }
    private function getMatchData($idioma,$region,$matchId){
        $queues = json_decode(file_get_contents('jsons/estaticos/queues.js'));
        $responseData = $this->sendApiRequest($region,env('MATCH_OUTCOME_PATH'),$matchId);
        $summonersInfo = $this->pullSummonersInfo($responseData);
        $summonersData = $this->pullSummonersData($idioma,$responseData, $summonersInfo);
        $match = $this->buildMatch($queues,$responseData,$summonersData);
        return $match;
    }
    private function buildMatchHistory($idioma,$region,$matchHistoryIds){
        $matchHistory = [];
        $gameNumber = 1;
        foreach($matchHistoryIds as $match){
            $matchData[] = $this->getMatchData($idioma,$region,$match['gameId']);
            if($gameNumber == 20){
                break;
            }
            $gameNumber++;
        }
        return $matchData;
    }
    //principal
    public function getMatchHistory($idioma,$region,$sumonerName){
        $servers = json_decode(file_get_contents('jsons/estaticos/servers.json'));
        $region = collect(collect($servers)['regions'])->where('region','=',$region)->first()->id;
        $sumonerData = $this->getSummonerData($region,$sumonerName);
        $mathcHistoryIds = $this->getMatchHistoryIds($region,$sumonerData['accountId']);
        $matchHistory = $this->buildMatchHistory($idioma,$region,$mathcHistoryIds);
        return array(
            "summonerName" => $sumonerData['name'],
            "matchHistory" => $matchHistory
        );
    }
}
