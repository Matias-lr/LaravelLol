<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class liveGameController extends Controller
{
    private function sendApiRequest($region,$path,$requestData){
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://'.$region.env('API_URL'),
        ]);
        $key = '?api_key='.env('API_KEY');
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
    private function sendStaticApiRequest($requestData){
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => env('STATIC_DATA_PATH'),
        ]);
        try{
            $res = $client->request('GET', $requestData);
            return json_decode($res->getBody(), true);
        }catch (Exception $e){
            return $e;
        }
    }
    /*async function checkActiveGame(summonerId, region) {
    const summonerGameData = await rp({
        uri: `https://${region}${riot}spectator/v4/active-games/by-summoner/${summonerId}?api_key=${process.env.LOL_KEY}`,
        json: true
    })
    .catch(err => console.log("checkActiveGame ERROR: " + err))
    return summonerGameData
}*/
    private function getSummonerMap($maps,$summonerMapId){
        $map = collect($maps)->where('mapId','=',$summonerMapId)->first();

        return $map->mapName;
    }
    private function getSummonerSpell($spells,$spellId){
        foreach($spells->data as $spell){
            if($spell->key == $spellId){
                $summonerSpell = $spell->name;
            };
        }
        return $summonerSpell;
    }
    private function getSummonerSpellId($spells,$spellId){
        foreach($spells->data as $spell){
            if($spell->key == $spellId){
                $summonerSpell = $spell->id;
            };
        }
        return $summonerSpell;
    }
    private function GetSummonerLeagueData($id,$region){
        $rank = $this->sendApiRequest($region,'lol/league/v4/entries/by-summoner/',$id);
        foreach($rank as $r){
            $json = array(
                'queue' => $r['queueType'],
                'tier' => $r['tier'],
                'rank' => $r['rank'],
                'points' => $r['leaguePoints'],
                'wins' => $r['wins'],
                'loses' => $r['losses'],
                '%' => round((100 * $r['wins'])/($r['wins'] + $r['losses']),0).'%'
            );
        }
        if(!$rank){
            return 'el invocador no tiene liga';
        }else{
            return $json;
        }
    }
    private function GetSubPerks($runes,$ids){

    }
    private function GetSummonerRunes($perks,$runes){
        $perkStyle = collect($runes)->where('id','=',$perks['perkStyle'])->first();
        $perkSubStyle = collect($runes)->where('id','=',$perks['perkSubStyle'])->first();
        $perkIds = $perks['perkIds'];
        foreach(collect($perkSubStyle->slots) as $h){
            foreach(collect($h->runes) as $h){
                if($h->id == $perkIds[4]){
                    $nameSP1 = $h;
                }
                if($h->id == $perkIds[5]){
                    $nameSP2 = $h;
                }
            }
        }
        $perk1Id = collect($perkStyle->slots[0]->runes)->where('id','=',$perkIds[0])->first()->icon;
        $perk2Id = collect($perkStyle->slots[1]->runes)->where('id','=',$perkIds[1])->first()->icon;
        $perk3Id = collect($perkStyle->slots[2]->runes)->where('id','=',$perkIds[2])->first()->icon;
        $perk4Id = collect($perkStyle->slots[3]->runes)->where('id','=',$perkIds[3])->first()->icon;
        
        $json = array(
            'perkPrimaryStyle' => $perkStyle->name,
            'perkPrimaryStyleIcon' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$perkStyle->icon,
            'perk1' => collect($perkStyle->slots[0]->runes)->where('id','=',$perkIds[0])->first()->name,
            'perk1Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$perk1Id,
            'perk2' => collect($perkStyle->slots[1]->runes)->where('id','=',$perkIds[1])->first()->name,
            'perk2Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$perk2Id,
            'perk3' => collect($perkStyle->slots[2]->runes)->where('id','=',$perkIds[2])->first()->name,
            'perk3Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$perk3Id,
            'perk4' => collect($perkStyle->slots[3]->runes)->where('id','=',$perkIds[3])->first()->name,
            'perk4Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$perk4Id,
            'perkSecondaryStyle' => $perkSubStyle->name,
            'perk5' => $nameSP1->name, //collect($perkStyle->slots[0]->runes)->where('id','=',$perkIds[4])->first()->name,
            'perk5Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$nameSP1->icon,
            'perk6' => $nameSP2->name, //collect($perkStyle->slots[0]->runes)->where('id','=',$perkIds[5])->first()->name,
            'perk6Image' => 'https://ddragon.leagueoflegends.com/cdn/img/'.$nameSP2->icon,
        );
        return $json;
    }
    private function getSummonersByTeam($runes,$region,$summonerSpell,$champions,$idioma,$players,$teamId){
        $summonersByTeam = collect($players)->where('teamId','=',$teamId)->all();
        $json = array();
        foreach($summonersByTeam as $summoner){
            $champion = collect($champions->data)->where('key','=',$summoner['championId'])->first()->name;
            array_push($json,array(
                "name" => $summoner['summonerName'],
                "profileIconId" => "http://ddragon.leagueoflegends.com/cdn/10.4.1/img/profileicon/".$summoner['profileIconId'].".png",
                "champion" => $champion,
                'championImage' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.collect($champions->data)->where('key','=',$summoner['championId'])->first()->id.'.png',
                "summonerSpellD" => $this->getSummonerSpell($summonerSpell,$summoner['spell1Id']),
                "spellDImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$summoner['spell1Id']).'.png',
                "summonerSpellf" => $this->getSummonerSpell($summonerSpell,$summoner['spell2Id']),
                "spellFImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$summoner['spell2Id']).'.png',
                "SummonerRank" => $this->GetSummonerLeagueData($summoner['summonerId'],$region),
                "runas" => $this->GetSummonerRunes($summoner['perks'],$runes)
            ));
        }
        

        return $json;
    }
    private function getBanedChampionsByTeam($champions,$Bchampions,$team){
        $json = [];
        $BchampsByTeam = collect($Bchampions)->where('teamId','=',$team)->all();
        foreach($BchampsByTeam as $c){
            $ch = collect($champions->data)->where('key','=',$c['championId'])->first()->name;
            array_push($json,array(
                'champion' => $ch,
                'championIcon' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.collect($champions->data)->where('key','=',$c['championId'])->first()->id.'.png'
            ));
        }
        return $json;
    }
    private function getPrincipalMatchData($region,$idioma,$SummonerMatch,$Summoner){
        $maps = collect(json_decode(file_get_contents('jsons/estaticos/maps.js')));

        $summonerMapId = $SummonerMatch['mapId'];

        $map = $this->getSummonerMap($maps,$summonerMapId);
        
        $summonerSpell = json_decode(file_get_contents('jsons/'.$idioma.'/summoner.json'));

        $queue = json_decode(file_get_contents('jsons/estaticos/queues.js'));

        $champions = json_decode(file_get_contents('jsons/'.$idioma.'/champions.json'));

        $baned = null;

        $runes = json_decode(file_get_contents('jsons/'.$idioma.'/runesReforged.json'));
        if($SummonerMatch['gameType'] != 'CUSTOM_GAME'){
            $queueId = collect($queue)->where('queueId','=',$SummonerMatch['gameQueueConfigId'])->first()->description;
            if(strpos($queueId,'Ranked') == true){
                $baned = array(
                    "blue" => $this->getBanedChampionsByTeam($champions,$SummonerMatch['bannedChampions'],100),
                    "red" => $this->getBanedChampionsByTeam($champions,$SummonerMatch['bannedChampions'],200),
                );
            }
        }else{
            $queueId = $SummonerMatch['gameType'];
        }
        
        $init = $SummonerMatch["gameLength"];
        $minutes = floor(($init / 60) % 60);
        $seconds = $init % 60;
        if(strlen($seconds) == 1){
            $seconds = '0'.$seconds;
        }
        if(strlen($minutes) == 1){
            $minutes = '0'.$minutes;
        }

        $json = array(
            "Invocador" => $Summoner['name'],
            "iconUrl" => 'http://ddragon.leagueoflegends.com/cdn/10.3.1/img/profileicon/'.$Summoner['profileIconId'].'.png',
            "lvl" => $Summoner['summonerLevel'],
            "gameMode" => $SummonerMatch['gameMode'],
            "queue" => $queueId,//$SummonerMatch['gameQueueConfigId'],
            "Map" => $map,
            "GameDuration" => $minutes.':'.$seconds,
            "teams" => array(
                "blue" => $this->getSummonersByTeam($runes,$region,$summonerSpell,$champions,$idioma,$SummonerMatch['participants'],100),
                "red" => $this->getSummonersByTeam($runes,$region,$summonerSpell,$champions,$idioma,$SummonerMatch['participants'],200)
            ),
            "bannedChampions" => $baned
        );

        return $json;
    }
    public function getLiveGame($idioma,$region,$summonerName){
        $servers = json_decode(file_get_contents('jsons/estaticos/servers.json'));
        $region = collect(collect($servers)['regions'])->where('region','=',$region)->first()->id;
        $Summoner = $this->sendApiRequest($region,env('SUMMONERS_PATH'),$summonerName);
        $SummonerId = $Summoner['id'];
        $SummonerMatch = $this->sendApiRequest($region,env('LIVE_MATCH_PATH'),$SummonerId);
        //$SummonerMatch = json_decode(file_get_contents(public_path().'/jsons/test.json'), true);
        if($SummonerMatch != 0){
            $getMatchData = $this->getPrincipalMatchData($region,$idioma,$SummonerMatch,$Summoner);
            return $getMatchData;
        }else if($SummonerMatch == 0){
            return 'el invocador no se encuentra en partida';
        }else{
            return 'algo salio mal';
        }
    }
}
