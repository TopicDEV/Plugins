<?php
 
/*
__PocketMine Plugin__
name=HungerGames
description=HungerGames Plugin
version=1.0.0
author=sekjun9878
class=HungerGames
apiversion=8,9
*/
		
class HungerGames implements Plugin{	
	private $api;
	
	private $minute = 30;
	private $klass = "none";
	private $playerspawncount = 0;
	
	private $gamestarted = false;
	
	private $server;
	private $servname;
	
	private $spawn_loc = array(
	array(170.5, 72, 170.5),
	array(170.5, 72, 163.5),
	array(175.5, 72, 157.5),
	array(180.5, 72, 152.5),
	array(188.5, 72, 152.5),
	array(196.5, 72, 170.5),
	array(201.5, 72, 157.5),
	array(206.5, 72, 163.5),
	array(206.5, 72, 170.5),
	array(206.5, 72, 177.5),
	array(201.5, 72, 183.5),
	array(195.5, 72, 188.5),
	array(188.5, 72, 188.5),
	array(180.5, 72, 188.5),
	array(175.5, 72, 183.5),
	array(170.5, 72, 177.5)
	);
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}
	
	public function init(){
	
		$this->api->console->register("hg", "Hunger Games", array($this, "commandHandler"));
		
		$this->api->addHandler("player.join", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.death", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.spawn", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.respawn", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.connect", array($this, "eventHandler"), 100);
		$this->api->addHandler("player.block.touch", array($this, "eventHandler"), 100);
		$this->api->addHandler("entity.health.change", array($this, "eventHandler"), 100);
		
		$this->api->level->loadLevel("HungerGames");
		
		$this->api->schedule(1200, array($this, "minuteSchedule"), array(), true);
		
		$this->api->level->get("HungerGames")->setSpawn(new Vector3(188.5, 75, 170));
		
		$this->servname = $this->server->name;
		$this->server->name = "[OPEN] ".$this->servname;
	}
	
	private function getNextSpawn()
	{
		$this->playerspawncount = $this->playerspawncount + 1;
		if($this->playerspawncount <= 16)
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
			case "player.join":
				if($this->gamestarted == true)
				{
					$data->close();
				}
				break;
			case "player.respawn":
			case "player.death":
				if($this->gamestarted == true)
				{
					$data['player']->close();
				}
				if(count($this->api->player->getAll()) <= 1)
				{
					$this->api->chat->broadcast("Игра закончена. Сервер выключается...");
					rmdir('./worlds/'.$this->server->api->getProperty("level-name").'/chunks/');
					rmdir('./worlds/'.$this->server->api->getProperty("level-name").'/');
					rmdir('./players/');
					$this->api->console->run("stop");
				}
				break;
			case 'player.connect':
				if($this->gamestarted === true)
				{
					return false;
				}
				break;
			case 'player.block.touch':
				if($this->gamestarted === false)
				{
					return false;
				}
				break;
			case 'entity.health.change':
				if($this->gamestarted === false)
				{
					return false;
				}
				break;
			case "player.spawn":
				if($this->gamestarted === true)
				{
					$data->close("Игра уже началась!", false);
					break;
				}
				
				$nextspawn = $this->getNextSpawn();
				$data->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
				$data->blocked = true;
				
				$data->sendChat("----------------------------------------------------");
				$data->sendChat("** Приветствуем на нащем сервере!");
				$data->sendChat("** Сейчас играет: ".count($this->api->player->getAll())."/".$this->server->maxClients);
				$data->sendChat("** Удачи!");
				$data->sendChat("----------------------------------------------------");
				break;
		}
	}
	
	private function startGame()
	{		
		$this->gamestarted = true;
		$this->server->name = "[HG START] ".$this->servname;
		$this->playerspawncount = 0;
		foreach($this->api->player->getAll() as $p)
		{
			$nextspawn = $this->getNextSpawn();
			$p->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
			
			$p->setGamemode(0);
			
			$p->blocked = false;
			
			if ($this->klass === "hunter"){
			$this->api->chat->sendTo(false, "[HungerGames]В этом раунде вы играете за Охотника!", $p);
			$this->api->ban->commandHandler("give".$p."272 1", false);
			$this->api->ban->commandHandler("give".$p."260 1", false);
			$this->api->ban->commandHandler("give".$p."299 1", false);
			}
			if ($this->klass === "miner"){
			$this->api->chat->sendTo(false, "[HungerGames]В этом раунде вы играете за Шахтера!", $p);
			$this->api->ban->commandHandler("give".$p."274 1", false);
			$this->api->ban->commandHandler("give".$p."46 2", false);
			$this->api->ban->commandHandler("give".$p."259 1", false);
			$this->api->ban->commandHandler("give".$p."2 16", false);
			}
			if ($this->klass === "none"){
			$this->api->chat->sendTo(false, "[HungerGames]Вы не успели выбрать класс!", $p);
			$this->api->chat->sendTo(false, "[HungerGames]Класс установлен на стандартный (Охотник).", $p);
			$this->api->ban->commandHandler("give".$p."272 1", false);
			}
		}
	}	
 
	public function minuteSchedule()
	{
		$this->minute--;
		if($this->minute > 25 and $this->minute <= 30)
		{
			$this->api->chat->broadcast("----------------------------------------------------");
			$this->api->chat->broadcast("** Приветствуем на нащем сервере!");
			$this->api->chat->broadcast("** Сейчас играет: ".count($this->api->player->getAll())."/".$this->server->maxClients);
			$this->api->chat->broadcast("** Удачи!");
			$this->api->chat->broadcast("** ".($this->minute-25)." минут до начала игры.");
			$this->api->chat->broadcast("----------------------------------------------------");
		}
		if($this->minute == 25)
		{
			$this->api->chat->broadcast("** Игра началась!");
			$this->api->chat->broadcast("** Игра началась!");
			$this->api->chat->broadcast("** Игра началась!");
			$this->startGame();
		}
		if($this->minute < 25 and $this->minute > 1)
		{
			$this->api->chat->broadcast(($this->minute)." минут осталось");
		}
		if($this->minute == 1)
		{
			$this->api->chat->broadcast("Осталась 1 минута!");
			foreach($this->api->player->getAll() as $p)
			{
				$this->playerspawncount = 0;
				$nextspawn = $this->getNextSpawn();
				$p->teleport(new Vector3($nextspawn[0], $nextspawn[1], $nextspawn[2]));
			}
		}
		if($this->minute == 0)
		{
			$this->api->chat->broadcast("Игра закончена. Сервер выключается...");
			$this->api->console->run("stop");
		}
	}
	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		if($cmd != "hg")
		{
			$output .= "Called via wrong command. Exiting..";
			return $output;
		}
			
		switch(array_shift($params)){
			case "settimer":
			if ($this->api->ban->isOp($issuer)){
				$this->minute = array_shift($params);
				$output .= "Успешно! Осталось: ".$this->minute;
				break;
			}else{$output .= "Вы не OP";break;}
			case "gettimer":
				$output .= "Осталось: ".$this->minute;
				break;
			case "class":
			if($this->gamestarted === true){
			$output .= "Игра уже началась! Вы не можете сменить класс во время игры!";
			break;
			}else{
				if ($params[1] === "hunter"){
				$this->klass = "hunter";
				$output .= "Вы выбрали класс \"Охотник\"!";
				break;
				}
				if ($params[1] === "miner"){
				$this->klass = "miner";
				$output .= "Вы выбрали класс \"Шахтер\"!";
				break;
				}
				else{
				$output .= "Вы ввели не существующий класс!";
				break;
				}
			}
		}
		return $output;
	}
	
	public function __destruct()
	{
		
	}
 
}