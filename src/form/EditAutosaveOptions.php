<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler\form;

use DaisukeDaisuke\AwaitFormOptions\FormOptions;
use DaisukeDaisuke\GameRuleToggler\Main;
use pocketmine\player\Player;
use cosmicpe\awaitform\FormControl;

final class EditAutosaveOptions extends FormOptions implements SettingFormInterface{

	public function __construct(private Main $plugin, private Player $player, private string $title){
	}

	public function getOptions() : array{
		return ["result" => $this->flow()];
	}

	private function flow() : \Generator{
		$current = $this->plugin->getSetting()->getAutosaveInterval();

		[$input] = yield from $this->request([
			FormControl::input(
				"Autosave interval (seconds, >=30)",
				(string) $current
			)
		]);

		if($input === "" || !$this->is_natural($input) || ((int) $input) < 30){
			$this->player->sendToastNotification("GameRuleTogglers", "入力された数値は無効です！");
			return false;
		}

		$this->plugin->getConfig()->setNested("autosave.interval", (int) $input);
		$this->plugin->saveConfig();
		$this->plugin->reloadSetting();

		$this->player->sendToastNotification("GameRuleTogglers", "オートセーブ間隔を保存しました！");
		return true;
	}

	protected function userDispose() : void{
		unset($this->plugin, $this->player);
	}

	function is_natural($val){
		return (bool) preg_match('/\A[1-9][0-9]*\z/', $val);
	}

	public function getTitle() : string{
		return $this->title;
	}
}