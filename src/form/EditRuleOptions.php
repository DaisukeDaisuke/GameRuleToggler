<?php

declare(strict_types=1);


namespace DaisukeDaisuke\GameRuleToggler\form;

use DaisukeDaisuke\AwaitFormOptions\FormOptions;
use DaisukeDaisuke\GameRuleToggler\Main;
use pocketmine\player\Player;
use cosmicpe\awaitform\FormControl;

final class EditRuleOptions extends FormOptions implements SettingFormInterface{

	private Main $plugin;
	private Player $player;
	private string $ruleKey;
	private string $title;

	public function __construct(Main $plugin, Player $player, string $ruleKey, string $title){
		$this->plugin = $plugin;
		$this->player = $player;
		$this->ruleKey = $ruleKey;
		$this->title = $title;
	}

	public function getOptions() : array{
		return ["result" => $this->flow()];
	}

	private function flow() : \Generator{
		$cfg = $this->plugin->getConfig();
		$base = "rules.{$this->ruleKey}.";

		$force = (bool) $cfg->getNested($base . "force", false);
		$opOnly = (bool) $cfg->getNested($base . "op-only", false);
		$value = (bool) $cfg->getNested($base . "value", true);
		$default = (bool) $cfg->getNested($base . "default", true);

		[$nForce, $nOpOnly, $nValue, $nDefault] = yield from $this->request([
			FormControl::toggle("Force", $force),
			FormControl::toggle("OP only", $opOnly),
			FormControl::toggle("Forced value", $value),
			FormControl::toggle("Default value", $default),
		]);

		$cfg->setNested($base . "force", (bool) $nForce);
		$cfg->setNested($base . "op-only", (bool) $nOpOnly);
		$cfg->setNested($base . "value", (bool) $nValue);
		$cfg->setNested($base . "default", (bool) $nDefault);
		$this->plugin->saveConfig();

		// 不変 Setting を再生成
		$this->plugin->reloadSetting();

		$this->player->sendToastNotification("GameRuleTogglers", "設定を保存しました！");

		return true;
	}

	protected function userDispose() : void{
		unset($this->plugin, $this->player);
	}

	public function getTitle() : string{
		return $this->title;
	}
}