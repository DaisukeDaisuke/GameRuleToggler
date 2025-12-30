<?php

declare(strict_types=1);


namespace DaisukeDaisuke\GameRuleToggler\form;

use DaisukeDaisuke\AwaitFormOptions\FormOptions;
use cosmicpe\awaitform\FormControl;
use pocketmine\player\Player;
use DaisukeDaisuke\AwaitFormOptions\exception\AwaitFormOptionsChildException;
use DaisukeDaisuke\GameRuleToggler\Main;

class PlayerSettingsOptions extends FormOptions{

	public function __construct(private Main $plugin, private Player $player){
	}

	/**
	 * @throws AwaitFormOptionsChildException
	 */
	public function editSettings() : \Generator{
		$pname = $this->player->getName();

		// 現在値を取得（Main 経由でルールの saved 値 / default を参照）
		$locator = $this->plugin->getLocatorRule()->get($pname);
		$coords  = $this->plugin->getShowCoordinatesRule()->get($pname);
		$respawn = $this->plugin->getDoImmediateRespawnRule()->get($pname);

		$form = [];

		if(!$this->plugin->getLocatorRule()->isForce()){
			$form["Locator"] = FormControl::toggle("Locator Bar", $locator);
		}

		if(!$this->plugin->getShowCoordinatesRule()->isForce()){
			$form["Coordinates"] = FormControl::toggle("Coordinates", $coords);
		}

		if(!$this->plugin->getDoImmediateRespawnRule()->isForce()){
			$form["Respawn"] = FormControl::toggle("Respawn", $respawn);
		}

		if(count($form) === 0){
			return;
		}

		$array = yield from $this->request($form);
		if(isset($array["Locator"])){
			$locatorResult = $array["Locator"];
			$this->plugin->setPlayerSettingAndApply($pname, 'locatorBar', (bool)$locatorResult, $this->player);
		}

		if(isset($array["Coordinates"])){
			$coordsResult = $array["Coordinates"];
			$this->plugin->setPlayerSettingAndApply($pname, 'showcoordinates', (bool)$coordsResult, $this->player);
		}
		if(isset($array["Respawn"])){
			$respawnResult = $array["Respawn"];
			$this->plugin->setPlayerSettingAndApply($pname, 'doImmediateRespawn', (bool)$respawnResult, $this->player);
		}
		$this->player->sendToastNotification("GameRuleToggler", "Settings saved!");
	}

	public function getOptions() : array{
		return [$this->editSettings()];
	}

	public function userDispose() : void{
		unset($this->player, $this->plugin);
	}
}