<?php
namespace tpafriends;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;
use pocketmine\scheduler\CallbackTask;

class main extends PluginBase {
	private $friendsapi;
	private $config;
	private $requests;
	public function onEnable(){
		$this->getLogger()->info("Loaded!");
		$this->friendsapi = $this->getServer()->getPluginManager()->getPlugin("Friends");
		$this->config = new Config($this->getDataFolder()."config.yml",Config::YAML,array(
				"usefriendapi" => true,
				"tpdelay" => 5
		));
		$this->config->save();
	}
	public function onCommand(CommandSender $sender,Command $command, $label,array $args){
		if ($sender instanceof Player){
		switch ($command->getName()){
			case "tpaccept":
				if(in_array($sender->getName(), $this->requests)){
					foreach ($this->requests as $requester => $accepter){
						if ($accepter === $sender->getName()){
							$pos = $sender->getPosition();
							$sender->sendMessage(TextFormat::GREEN."La requête de téleportation à été acceptée !");
							$requester = $this->getServer()->getPlayer($requester);
							$requester->sendMessage("Vous allez être téleporter dans ".$this->config->get("tpdelay")." seconde(s) !");
							$task = new teleport($this, $sender, $pos);
							$this->getServer()->getScheduler()->scheduleDelayedTask($task, 20 * $this->getConfig()->get("tpdelay"));
							$this->removeRequest($requester);
						}
					}
				}else{
					$sender->sendMessage("Aucune requête en attente :(");
				}
			break;
			case "tpa":
				if (isset($args[0])){
					$requestp = $this->getServer()->getPlayer($args[0]);
					if ($requestp instanceof Player){
						if ($this->config->get("usefriendapi") == true){
						if($this->friendsapi->isFriend($sender, $requestp->getName())){
							$sender->sendMessage("Sent tpa request!");
							$this->addRequest($sender, $requestp);
						}else{
							$sender->sendMessage("You are not friends with this player. :( \nDo /friend add [name] \nto request to be friends");
						}
						}else{
							$sender->sendMessage("La requête de téleportation a été envoyer avec succès !");
							$this->addRequest($sender, $requestp);
						}
					}else{
						$sender->sendMessage("Le joueur n'est pas en ligne !:(");
					}
				}else{
					$sender->sendMessage("USAGE: /tpa [name]");
				}
			break;
			
		}}
	}
	
	public function teleportPlayer(Player $player,Position $pos){
		$player->teleport($pos,$player->getYaw(),$player->getPitch());
		$player->sendPopup("Teleporté !");
	}
	
	public function addRequest(Player $player,Player $request){
		$this->requests[$player->getName()] = $request->getName();
		$request->sendMessage(TextFormat::GREEN."Player: ".$player->getName()." vous à envoyer une requête de tp ! POUR ACCEPTER: /tpaccept pour accepter \n ou ignorer le si vous ne voulez pas");
		$task = new removeRequest($this, $player);
		$this->getServer()->getScheduler()->scheduleDelayedTask($task, 10*20);
	}
	public function removeRequest(Player $player){
		if(isset($this->requests[$player->getName()])){
		unset($this->requests[$player->getName()]);
		}
	}
}
