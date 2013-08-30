 <?php
 /*
__PocketMine Plugin__
name=GamesControl
description=GamesControl Plugin
version=1.0.0
author=l1nux
class=GamesControl
apiversion=9
*/
 class GamesControl implements Plugin
  {
    private $api;

    public function __construct(ServerAPI $api, $server = false)
    {
      $this->api = $api;
    }

    public function init()
    {
	  //$this->config = new Config($this->api->plugin->configPath ($this)."players.yml", CONFIG_YAML, array());
      $this->api->addHandler("tile.update", array($this, "eventHandler"));
      $this->api->addHandler("player.block.touch", array($this, "eventHandler"));
	  $this->api->console->register('lobby', '[Control] TP Player to Lobby.', array($this, 'commandH'));
    }

    public function __destruct()
    {

    }

	
	
	public function commandH($cmd, $params, $issuer, $alias)
    {
	$output = "";
        switch ($cmd)
        {
            case "lobby":
				$usermap = $issuer->level->getName();
				if($usermap !== "Lobby"){
					if($usermap === "CreativeWorld"){
					$issuer->setGamemode(1);
					}
				$this->api->console->run("tp ".$username." w:Lobby", "console", false);
				}else{
				$output .= "[Experiense]You alredy in Lobby.";
				break;
				}
		return $output;
        }
    }
	
	
	
	
	
	
	
    public function eventHandler($data, $event)
    {
      switch ($event) {
		//Создание таблички, online
        case "tile.update":
          if ($data->class === TILE_SIGN) {
            if ($data->data["Text1"] != "Experiense-TP:" and $data->data["Text3"] != "Online:"){
              return;
			}
            $lvl = $data->data["Text2"];
			$data->data["Text4"] = count($this->api->player->get($lvl))."/50";
            if ($this->api->level->loadLevel($lvl) === false) {
              $this->api->chat->sendTo(false, "Мира не существует!", $data->data['creator']);
              break;
            }
            $this->api->chat->broadcast("Portal " . $data->data["Text1"] . " to ". $data->data["Text2"] . " created");
          }
          break;
		
		//Нажатие на табличку
        case "player.block.touch":
          $tile = $this->api->tile->get(new Position($data['target']->x, $data['target']->y, $data['target']->z, $data['target']->level));
          if ($tile === false) break;
          $class = $tile->class;
          switch ($class) {
            case TILE_SIGN:
              switch ($data['type']) {
                case "place":
				console($tile->data['Text1']."  ".$tile->data['Text2']);
                  if ($tile->data['Text1'] == "Experiense-TP:" and $tile->data['Text3'] == "Online:") {
                    $mapname = $tile->data['Text2'];
                    $username = $data['player']->username;
                    $this->api->level->loadLevel($mapname);
					if(count($this->api->player->get($mapname)) < 50){
                    if($this->api->console->run("tp ".$username." w:".$mapname, "console", false) == false){
					console("failure");
					}
						$this->api->chat->sendTo(false, "You joined in ".$mapname, $username);
						if($mapname = "CreativeWorld"){
						$data['player']->setGamemode(1);
						}
					break;
					}else{
					$this->api->chat->sendTo(false, "Room: ".$mapname." is full :(", $username);
					break;
					}
                  }
				  break;
              }
              break;
          }
          break;
		 //Здесь шото будетЬ
      }

    }
  }
