<?php

declare(strict_types=1);


namespace DaisukeDaisuke\GameRuleToggler\form;


use DaisukeDaisuke\AwaitFormOptions\FormOptions;
use cosmicpe\awaitform\FormControl;
use pocketmine\player\Player;
use DaisukeDaisuke\AwaitFormOptions\exception\AwaitFormOptionsChildException;
use DaisukeDaisuke\GameRuleToggler\Main;

class AdminSetAllOptions extends FormOptions{

	public function __construct(private Main $plugin, private Player $player){
	}

	/**
	 * @throws AwaitFormOptionsChildException
	 */
	public function flow() : \Generator{
		// ルール選択 (dropdown)
		$ruleKeys = ["locatorBar" => "Locator Bar", "showcoordinates" => "Show Coordinates", "doImmediateRespawn" => "Immediate Respawn"];
		$labels = array_values($ruleKeys);
		$form = [
			FormControl::dropdown("Target rule", $labels, $labels[0]),
			FormControl::toggle("Set to ON?", true),
			FormControl::toggle("Clear all saved settings for this rule?", false),
		];

		[$selectedLabel, $setOn, $clearAll] = yield from $this->request($form);

		// 選択ラベルをキーに戻す
		$selectedKey = array_search($selectedLabel, $labels, true);
		$ruleKey = array_keys($ruleKeys)[$selectedKey];

		// 実行
		$this->plugin->setAllRule($ruleKey, (bool)$setOn, (bool)$clearAll, $this->player);
		$this->player->sendMessage("[GameRuleToggler] setall executed: {$ruleKey} -> " . ($setOn ? "ON":"OFF") . ($clearAll ? " (cleared saved)" : ""));
	}

	public function getOptions(): array{
		return [$this->flow()];
	}

	public function userDispose(): void{
		unset($this->plugin, $this->player);
	}
}