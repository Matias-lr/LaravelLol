<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;


date_default_timezone_set('America/Santiago');

class BotController extends Controller
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
    private function getLastMatchs($region,$id,$summoner){
        $matchs = collect($this->sendApiRequestWithEx($region,'/lol/match/v4/matchlists/by-account/',$id.'?endIndex=10&')['matches']);
        $matchsIds = $matchs->map(function ($item) use ($region,$summoner) {
            $game = collect($this->sendApiRequest($region ,'/lol/match/v4/matches/',$item['gameId']));
            $game1 = collect($game['participantIdentities']);
            $game1 = collect($game1)->map(function ($item) use ($summoner){
                if($item['player']['summonerName'] == $summoner){
                    return $item['participantId'];
                }
            });
            foreach($game1 as $g){
                if($g != null){
                    $pa = $g;
                }
            }
            foreach(array($pa) as $s){
                foreach($game['participants'] as $p){
                    if($p['participantId'] == $s){
                        $gg = $p['teamId'];
                    }
                }
            }
            foreach(array($gg) as $g){
                foreach($game['teams'] as $t){
                    if($t['teamId'] == $g){
                        $hhh=$t['win'];
                    }
                }
            }
            return $hhh;
          });
          $w = 0;
          foreach($matchsIds as $ww){
              if($ww == 'Win'){
                $w++;
              }
          }
        return $w;
    }
    public function getBestChamps($region,$summoner){
        $champs = collect($this->sendApiRequest($region,'/lol/champion-mastery/v4/champion-masteries/by-summoner/',$summoner));
        $champs = $champs->take(3);
        $champions = json_decode(file_get_contents('jsons/en_US/champions.json'))->data;
        $json = [];
        foreach($champs as $c){
            $champ = collect($champions)->where('key','=',$c['championId'])->first();
            array_push($json,array(
                'champ' => $champ->name,
                'champImage' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.$champ->id.'.png',
                'masteryLvl' => $c['championLevel'],
                'lastPlayTime' => date('Y-m-d H:i:s', $c['lastPlayTime']/1000),
                'points' => $c['championPoints']
            ));
        }
        return $json;
    }
    private function getSummonerRank($region,$id,$ranks){
        $rank = collect($this->sendApiRequest($region,'/lol/league/v4/entries/by-summoner/',$id))->first();
        $league = $this->sendApiRequest($region,'/lol/league/v4/leagues/',$rank['leagueId']);
        if($league == 1){
            $json = 'unranked';
        }else{
            $json = array(
                'queueType' => $rank['queueType'],
                'leagueName' => $league['name'],
                'tier' => $rank['tier'],
                'tierId' => collect(collect($ranks)['leagues'])->where('name','=',$rank['tier'])->first()->id,
                'rank' => $rank['rank'],
                'points' => $rank['leaguePoints'],
                'wins' => $rank['wins'],
                'losses' => $rank['losses'],
                'veteran' => $rank['veteran'],
                'inactive' => $rank['inactive'],
                'freshBlood' => $rank['freshBlood'],
                'hotStreak' => $rank['hotStreak']
            );
        }
        return $json;
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
    private function getLastGame($region,$id,$summoner){
        $matchs = collect($this->sendApiRequestWithEx($region,'/lol/match/v4/matchlists/by-account/',$id.'?endIndex=1&')['matches']);
        $queue = json_decode(file_get_contents('jsons/estaticos/queues.js'));
        $champ = json_decode(file_get_contents('jsons/es_MX/champions.json'))->data;
        $maps = collect(json_decode(file_get_contents('jsons/estaticos/maps.js')));
        $summonerSpell = json_decode(file_get_contents('jsons/es_MX/summoner.json'));
        $matchsIds = $matchs->map(function ($item) use ($region,$summoner,$champ,$summonerSpell,$maps,$queue) {
            $game = collect($this->sendApiRequest($region ,'/lol/match/v4/matches/',$item['gameId']));
            $game1 = collect($game['participantIdentities']);
            $game1 = collect($game1)->map(function ($item) use ($summoner){
                if($item['player']['summonerId'] == $summoner){
                    return $item['participantId'];
                }
            });
            foreach($game1 as $g){
                if($g != null){
                    $pa = $g;
                }
            }
            foreach(array($pa) as $s){
                foreach($game['participants'] as $p){
                    if($p['participantId'] == $s){
                        $gg = $p['teamId'];
                        $xd = $p;
                    }
                }
            }
            foreach(array($gg) as $g){
                foreach($game['teams'] as $t){
                    if($t['teamId'] == $g){
                        $hhh=$t['win'];
                    }
                }
            }
            $champ = collect($champ)->where('key','=',$xd['championId'])->first();
            $json = array(
                'win' => $hhh,
                'champion' => $champ->name,
                'champImage' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.$champ->id.'.png',
                'spellD' => $this->getSummonerSpell($summonerSpell,$xd['spell1Id']),
                "spellDImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$xd['spell1Id']).'.png',
                'spellF' => $this->getSummonerSpell($summonerSpell,$xd['spell2Id']),
                "spellFImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$xd['spell2Id']).'.png',
                "kills" => $xd['stats']['kills'],
                "deaths" => $xd['stats']['deaths'],
                "assists" => $xd['stats']['assists'],
                "totalDamageToChamp" => $xd['stats']['totalDamageDealtToChampions'],
                "cs" => $xd['stats']['totalMinionsKilled'] + $xd['stats']['neutralMinionsKilled'],
                "gameMode" => $game['gameMode'],
                "map" => $map = collect($maps)->where('mapId','=',$game['mapId'])->first()->mapName,
                "time" => date('Y-m-d H:i:s', $game['gameCreation']/1000),
                "queue" =>collect($queue)->where('queueId','=',$game['queueId'])->first()->description

            );
            return $json;
          });
        return $matchsIds['0'];
    }
    private function getSummonerLiveGame($region,$summoner){
        $SummonerMatch = $this->sendApiRequest($region,env('LIVE_MATCH_PATH'),$summoner['id']);
        if($SummonerMatch == 0){
            $json = 'no esta en partida';
        }else{
            $queues = json_decode(file_get_contents('jsons/estaticos/queues.js'));
            $maps = json_decode(file_get_contents('jsons/estaticos/maps.js'));
            $participant =collect($SummonerMatch['participants'])->where('summonerId','=',$summoner['id'])->first();
            $champions = json_decode(file_get_contents('jsons/en_US/champions.json'))->data;
            $champ = collect($champions)->where('key','=',$participant['championId'])->first();
            $summonerSpell = json_decode(file_get_contents('jsons/es_MX/summoner.json'));
            $json = array(
                'GameMode' => $SummonerMatch['gameMode'],
                'GameType' => $SummonerMatch['gameType'],
                'queue' => collect($queues)->where('queueId','=',$SummonerMatch['gameQueueConfigId'])->first()->description,
                "map" => collect($maps)->where('mapId','=',$SummonerMatch['mapId'])->first()->mapName,//mapId,
                'champ' =>$champ->name,
                'champImage' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.$champ->id.'.png',
                'spellD' => $this->getSummonerSpell($summonerSpell,$participant['spell1Id']),
                "spellDImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$participant['spell1Id']).'.png',
                'spellF' => $this->getSummonerSpell($summonerSpell,$participant['spell2Id']),
                "spellFImage" => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/spell/'.$this->getSummonerSpellId($summonerSpell,$participant['spell2Id']).'.png'
            );
        }
        return $json;
    }
    public function SummonerData($region,$summoner){
        $servers = json_decode(file_get_contents('jsons/estaticos/servers.json'));
        $region = collect(collect($servers)['regions'])->where('region','=',$region)->first()->id;
        $ranks = json_decode(file_get_contents('jsons/estaticos/leagues.json'));
        $summonerData = collect($this->sendApiRequest($region,'/lol/summoner/v4/summoners/by-name/',$summoner));
        $summonerName = $summonerData['name'];
        $summonerLvl = $summonerData['summonerLevel'];
        $summonerIcon = 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/profileicon/'.$summonerData['profileIconId'].'.png';
        $lastGames = $this->getLastMatchs($region,$summonerData['accountId'],$summonerData['name']);
        $json = array(
            "nombre" => $summonerName,
            "nivel" => $summonerLvl,
            "icono" => $summonerIcon,
            "wins" => $lastGames,
            "loses" => 10 - $lastGames,
            "procentajeWin" => 100*$lastGames/10,
            "bestChamps" => $this->getBestChamps($region,$summonerData['id']),
            "rank" => $this->getSummonerRank($region,$summonerData['id'],$ranks),
            'lastGame' => $this->getLastGame($region,$summonerData['accountId'],$summonerData['id']),
            'liveGame' => $this->getSummonerLiveGame($region,$summonerData)
        );
        //$summonerData['id']
        //$summonerData['accountId']
        //return date('Y-m-d H:i:s', $lastGames[0]['timestamp']/1000);
        return $json;
    }
    public function ChampsTips($champ){
        $champions = collect(json_decode(file_get_contents('jsons/es_MX/champs/'.$champ.'.json'))->data->$champ);
        $champions = array(
            'name' => $champions['name'],
            'image' => 'http://ddragon.leagueoflegends.com/cdn/10.4.1/img/champion/'.$champions['id'].'.png',
            'lore' => $champions['blurb'],
            'title' => $champions['title'],
            'tipsAliado' =>  $champions['allytips'],
            'tipsEnemigo' =>  $champions['enemytips']
        );
        return $champions;
    }
}
