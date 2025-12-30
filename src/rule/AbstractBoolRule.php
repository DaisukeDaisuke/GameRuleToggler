<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler\rule;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\Server;

abstract class AbstractBoolRule{

	protected Config $config;
	protected bool $force;
	protected bool $forcedValue;
	protected bool $defaultValue;
	protected bool $forceOpOnly;
	protected bool $dirty = false;

	public function __construct(
		Config $config,
		bool $force,
		bool $forceOpOnly,
		bool $forcedValue,
		bool $defaultValue
	){
		$this->config = $config;
		$this->force = $force;
		$this->forceOpOnly = $forceOpOnly;
		$this->forcedValue = $forcedValue;
		$this->defaultValue = $defaultValue;
	}

	abstract protected function getRuleName() : string;
	abstract protected function getCommandKey() : string;

	protected function apply(Player $player, bool $value, bool $modifiable) : void{
		if($this->forceOpOnly && !Server::getInstance()->isOp($player->getName())){
			return;
		}

		$packet = GameRulesChangedPacket::create([
			$this->getRuleName() => new BoolGameRule($value, $modifiable)
		]);
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public function onJoin(Player $player) : void{
		if($this->force){
			$this->apply($player, $this->forcedValue, false);
			return;
		}

		$value = $this->config->get($player->getName(), $this->defaultValue);
		$this->apply($player, $value, true);
	}

	public function onSettingCommand(Player $player, string $command) : bool{
		if($this->force){
			return false;
		}

		if(!str_contains($command, $this->getCommandKey())){
			return false;
		}

		if(str_contains($command, "true")){
			$this->apply($player, true, true);
			$this->config->set($player->getName(), true);
		}elseif(str_contains($command, "false")){
			$this->apply($player, false, true);
			$this->config->set($player->getName(), false);
		}else{
			return false;
		}

		$this->dirty = true;
		return true;
	}

	public function save() : void{
		if($this->dirty){
			$this->config->save();
			$this->dirty = false;
		}
	}
}
