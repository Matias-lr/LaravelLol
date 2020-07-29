<?php 
  
  set_time_limit(20000);
function getData($url){
    $u = 'https://ddragon.leagueoflegends.com/';
    return file_get_contents($u.$url);
};
function getDataFullUrl($url){
    return file_get_contents($url);
};

   
/*if(file_put_contents( 'lenguajes.json',file_get_contents($url))) { 
    echo "se descargo la wea"; 
} 
else { 
    echo "falló."; 
}
*/
//BR1
//eun1
//EUW1
//JP1
//KR
//LA1
//LA2
//NA1
//
$version = json_decode(getData("api/versions.json"))[0];
$ruta = "jsons/";
$realms = json_decode(getData('realms/na.json'));//solo si encontramos problemas usando el version
$lenguajes = json_decode(getData('cdn/languages.json'));
if(!file_exists($ruta.'estaticos')){
    mkdir($ruta.'estaticos');
};
file_put_contents($ruta.'estaticos/seasons.js',getDataFullUrl('http://static.developer.riotgames.com/docs/lol/seasons.json'));
file_put_contents($ruta.'estaticos/queues.js',getDataFullUrl('http://static.developer.riotgames.com/docs/lol/queues.json'));
file_put_contents($ruta.'estaticos/maps.js',getDataFullUrl('http://static.developer.riotgames.com/docs/lol/maps.json'));
file_put_contents($ruta.'estaticos/gameMode.js',getDataFullUrl('http://static.developer.riotgames.com/docs/lol/gameModes.json'));
foreach($lenguajes as $l){
    if(!file_exists($ruta.$l)){
        mkdir($ruta.$l);
    };
    if($l != 'id_ID'){
        $champs =  getData('cdn/'.$version.'/data/'.$l.'/champion.json');
        file_put_contents($ruta.$l.'/champions.json',$champs);
        file_put_contents($ruta.$l.'/items.json',getData('cdn/'.$version.'/data/'.$l.'/item.json'));
        file_put_contents($ruta.$l.'/summoner.json',getData('cdn/'.$version.'/data/'.$l.'/summoner.json'));
        file_put_contents($ruta.$l.'/profileicon.json',getData('cdn/'.$version.'/data/'.$l.'/profileicon.json'));
        file_put_contents($ruta.$l.'/summoner.json',getData('cdn/'.$version.'/data/'.$l.'/summoner.json'));
        file_put_contents($ruta.$l.'/runesReforged.json',getData('cdn/'.$version.'/data/'.$l.'/runesReforged.json'));
        /*foreach(json_decode($champs)->data as $champ){
            if(!file_exists($ruta.$l.'/champs')){
                mkdir($ruta.$l.'/champs');
            };
            file_put_contents($ruta.$l.'/champs/'.$champ->id.'.json',getData('cdn/'.$version.'/data/'.$l.'/champion/'.$champ->id.'.json'));
        }*/
    }
}
?>