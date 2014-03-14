<?php
 
/*
__PocketMine Plugin__
name=TheWalls
description=TheWalls
version=08.03.2014
author=EkiFoX || Topic
class=TheWalls
apiversion=13
*/
		
class TheWalls implements Plugin{	
	private $api;

	private $interact = false;
	private $pd = array();

	// TEAM
	private $team1 = array();
	private $team2 = array();
	private $team3 = array();
	private $team4 = array();
	// TEAM
	private $userteam = "1";

	private $minute = 20;
	
	private $gamestarted = false;
	
	private $server;
	private $servname;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}
	
	public function init(){
	
		$this->api->console->register("info", "TheWalls Command", array($this, "handleCommand"));
		$this->api->console->register("lobby", "TheWalls Command", array($this, "handleCommand"));
		$this->api->console->register("help", "TheWalls Command", array($this, "handleCommand"));
		$this->api->console->register("spawn", "TheWalls Command", array($this, "handleCommand"));
      	$this->api->ban->cmdWhitelist("info");
      	$this->api->ban->cmdWhitelist("lobby");
      	$this->api->ban->cmdWhitelist("help");
      	$this->api->ban->cmdWhitelist("spawn");
		
		$this->api->addHandler("player.death", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.spawn", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.block.touch", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.block.break", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.interact", array($this, "eventHandler"), 100);
		
		$this->api->level->loadLevel("Walls");
		
		$this->api->schedule(1200, array($this, "minuteSchedule"), array(), true);
		
		$this->api->level->get("Walls")->setSpawn(new Vector3(119, 119, 142));
		
		$this->servname = $this->server->name;
		$this->server->name = "[OPEN] ".$this->servname;
	}
	
	private function getNextSpawn()
	{
		$this->playerspawncount = $this->playerspawncount + 1;
		if($this->playerspawncount <= 40)
			return $this->spawn_loc[$this->playerspawncount-1];
		else
		{
			$this->playerspawncount = 1;
			return $this->spawn_loc[$this->playerspawncount-1];
		}
	}
	
	public function eventHandler($data, $event)
	{
		switch($event)
		{
			case "player.death":
				$player = $data['player']->username;
				$this->api->chat->broadcast("[Walls] Player ".$player." died");
				$this->pd[] = $data->iusername;
				unset($player, $this->team1);
				unset($player, $this->team2);
				unset($player, $this->team3);
				unset($player, $this->team4);
				break;
				case "player.quit":
					if($key = array_search($this->team1, $player)){
					unset($this->team1[$key]);
				}elseif($key = array_search($this->team2, $player)){
					unset($this->team2[$key]);
				}elseif($key = array_search($this->team3, $player)){
					unset($this->team3[$key]);
				}elseif($key = array_search($this->team4, $player)){
					unset($this->team4[$key]);
				}
				break;
			case "player.respawn":
				$player = $data->username;
				$player->sendChat("[Walls] You can see own stat /stat");
				$player->teleport(new Vector3(119, 119, 142));
			break;
			case 'player.block.touch':
				if($this->gamestarted === false)
				{
					return false;
				}
				break;

			case 'player.block.break': 
				$block = $data['target'];
				if ($block->getID() == 12) { return false; }
				if ($block->getID() == 13) { return false; }
				if ($block->getID() == 24) { return false; }
				if ($block->getID() == 24) { return false; }
				break;

			case 'player.interact':
				if($this->interact === false)
				{
					return false;
				}
				$player = $this->api->player->getByEID($data["entity"]->eid);
				if(in_array($player, $this->pd)) 
				{
                   	return false;
                }
				break;

			case "player.spawn":
				if($this->gamestarted === true)
				{
					$data->close("The Game has already started!", false);
					break;
				}
				
				$this->api->chat->broadcast("[Walls] Player ".$data->username." join (".count($this->api->player->getAll())."/".$this->server->maxClients.")");
				
				$data->sendChat("===========================");
				$data->sendChat(" Welcome to TheWalls");
				$data->sendChat(" Info - /info");
				$data->sendChat(" Left Game - /lobby");
				$data->sendChat("===========================");     
				$this->api->schedule(1, array($this, "SpawnLobby"), $data);
				$name = $data->iusername;
				if($this->userteam == "1"){
					$this->team1[] = $name;
					$this->api->chat->broadcast($data->username." has joined to Red team");
					$this->userteam = "2";
					break;
				}

				
				if($this->userteam == "2"){
					$this->team2[] = $name;
					$this->api->chat->broadcast($data->username." has joined to Green team");
					$this->userteam = "3";
					break;
				}
				
				if($this->userteam == "3"){
					$this->team3[] = $name;
					$this->api->chat->broadcast($data->username." has joined to Blue team");
					$this->userteam = "4";
					break;
				}
				
				if($this->userteam == "4"){
					$this->team4[] = $name;
					$this->api->chat->broadcast($data->username." has joined to Yellow team");
					$this->userteam = "1";
					break;
				}
				break;
		}
	}

	public function SpawnLobby($player, $data)
    {
         $player->teleport(new Vector3(119, 119, 142));
    }
	
	private function startGame()
	{		
		$this->gamestarted = true;
		$this->server->name = "[GAME] ".$this->servname;
		foreach($this->team1 as $p)
		{
			$p = $this->api->player->get($p);
			if(isset($p)){
			$p->teleport(new Vector3(100, 100, 100));
			$p->setGamemode(0);
			}
		}
		foreach($this->team2 as $p)
		{
			$p = $this->api->player->get($p);
			if(isset($p)){
			$p->teleport(new Vector3(100, 100, 100));
			$p->setGamemode(0);
			}
		}
		foreach($this->team3 as $p)
		{
			$p = $this->api->player->get($p);
			if(isset($p)){
			$p->teleport(new Vector3(100, 100, 100));
			$p->setGamemode(0);
			}
		}
		foreach($this->team4 as $p)
		{
			$p = $this->api->player->get($p);
			if(isset($p)){
			$p->teleport(new Vector3(100, 100, 100));
			$p->setGamemode(0);
			}
		}
	}	
 
	private function wallsCut($selection){
		$blocks = array();
		$level = $this->api->level->get($selection[0][3]);
		$startX = min($selection[0][0], $selection[1][0]);
		$endX = max($selection[0][0], $selection[1][0]);
		$startY = min($selection[0][1], $selection[1][1]);
		$endY = max($selection[0][1], $selection[1][1]);
		$startZ = min($selection[0][2], $selection[1][2]);
		$endZ = max($selection[0][2], $selection[1][2]);
		$air = new AirBlock();
		for($x = $startX; $x <= $endX; ++$x){
			$blocks[$x - $startX] = array();
			for($y = $startY; $y <= $endY; ++$y){
				$blocks[$x - $startX][$y - $startY] = array();
				for($z = $startZ; $z <= $endZ; ++$z){
					$b = $level->getBlock(new Vector3($x, $y, $z));
					$blocks[$x - $startX][$y - $startY][$z - $startZ] = chr($b->getID()).chr($b->getMetadata());
					$level->setBlockRaw(new Vector3($x, $y, $z), $air);
					unset($b);
				}
			}
		}
	}
 
	private function wallsDown(){
		$level = $this->api->level->get("Walls");
		//1 Сторона
		$this->wallsDown(array(
		array("111", "74", "151", "Walls"),
		array("49", "51", "151")
		));
		
		$this->wallsDown(array(
		array("110", "73", "153", "Walls"),
		array("110", "51", "212")
		));
		//2 Сторона
		$this->wallsDown(array(
		array("128", "51", "212", "Walls"),
		array("128", "73", "152")
		));
		
		$this->wallsDown(array(
		array("129", "73", "151", "Walls"),
		array("189", "51", "151")
		));
		//3 Сторона
		$this->wallsDown(array(
		array("189", "51", "133", "Walls"),
		array("129", "73", "133")
		));
		
		$this->wallsDown(array(
		array("128", "73", "72", "Walls"),
		array("128", "51", "132")
		));
		//4 Сторона
		$this->wallsDown(array(
		array("49", "73", "133", "Walls"),
		array("109", "51", "133")
		));
		
		$this->wallsDown(array(
		array("110", "51", "132", "Walls"),
		array("110", "73", "72")
		));
	}


	public function minuteSchedule()
	{
		$this->minute--;
		if($this->minute > 15 and $this->minute <= 20)
		{
			$this->api->chat->broadcast("[Walls] ".($this->minute-15)." minutes before the game starts");
		}

		if($this->minute == 15)
		{
			$this->api->chat->broadcast("[Walls] Game Starts");
			$this->startGame();
		}

		if($this->minute < 15 and $this->minute > 5)
		{
			$this->api->chat->broadcast("[Walls] ".($this->minute)." minutes before walls fall");
		}

		if($this->minute == 5)
		{
			$this->wallsDown();
			$this->interact = true;
			$this->api->chat->broadcast("[Walls] Walls fell down");
		}

		if($this->minute == 0)
		{
			$this->api->chat->broadcast("[Walls] Game End");
			$this->api->console->run("stop");
		}
	}

	public function handleCommand($cmd, $arg, $issuer){
    	switch($cmd){
      		case "info":
       		$player = $issuer;
       		$player->sendChat("[Walls] This server is part of the project Exepriense.RU");
        	break;
        	case "lobby":
        		if($this->gamestarted == false){
        			$issuer->sendChat("[Walls] Command available after the game starts");
        		}
        		if($this->gamestarted == true){
        			$this->api->console->run("kill $player","console");
        		}	
			break;
			case "help":
			$player = $issuer;
			$player->sendChat("[Walls] Command is not available");
			break;
			case "spawn":
			$player = $issuer;
			$player->sendChat("[Walls] Command is not available");
			break;
        }
        }
	
	public function __destruct()
	{
		
	}
 
}
