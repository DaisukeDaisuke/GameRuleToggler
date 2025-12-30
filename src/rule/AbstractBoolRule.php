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
	protected bool $forceOpOnly;
	protected bool $forcedValue;
	protected bool $defaultValue;
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
		$packet = GameRulesChangedPacket::create([
			$this->getRuleName() => new BoolGameRule($value, $modifiable)
		]);
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	// 公開版：特定プレイヤーへ即時適用（フォーム等から使用）
	public function applyToPlayer(Player $player, bool $value, bool $modifiable) : void{
		$this->apply($player, $value, $modifiable);
	}

	// 保存値を上書き（フォーム等から使用）
	public function setPlayerSavedValue(string $playerName, bool $value) : void{
		$this->config->set($playerName, $value);
		$this->dirty = true;
	}

	// 全設定を消す
	public function clearAllSaved() : void{
		$this->config->setAll([]); // Config::setAll は存在する想定
		$this->config->save();
		$this->dirty = false;
	}

	// onJoin の既存ロジック
	public function onJoin(Player $player) : void{
		if($this->force){
			$this->apply($player, $this->forcedValue, false);
			return;
		}

		if(!$this->config->exists($player->getName())){
			$this->setPlayerSavedValue($player->getName(), $this->defaultValue);
		}

		$value = $this->config->get($player->getName(), $this->defaultValue);
		$this->apply($player, $value, true);
	}

	/**
	 * @return bool 処理したら true
	 */
	public function onSettingCommand(Player $player, string $command) : bool{
		if($this->force){
			return false;
		}

		// op-only チェック
		if($this->forceOpOnly && !Server::getInstance()->isOp($player->getName())){
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

	public function isForce() : bool{
		return $this->force;
	}

	public function get(Player|string $player) : bool{
		if($player instanceof Player){
			$player = $player->getName();
		}
		return $this->config->get($player, $this->defaultValue);
	}

	public function isForcedValue() : bool{
		return $this->forcedValue;
	}

	public function isForceOpOnly() : bool{
		return $this->forceOpOnly;
	}
}
