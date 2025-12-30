<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler\form;

use DaisukeDaisuke\AwaitFormOptions\FormOptions;
use DaisukeDaisuke\GameRuleToggler\Main;
use pocketmine\player\Player;
use cosmicpe\awaitform\FormControl;

final class ConfigOptions extends FormOptions {

	private Main $plugin;
	private Player $player;

	public function __construct(Main $plugin, Player $player){
		$this->plugin = $plugin;
		$this->player = $player;
	}

	public function getOptions() : array{
		return ["result" => $this->flow()];
	}

	private function flow() : \Generator{
		[$choice] = yield from $this->request([
			FormControl::dropdown(
				"Edit target",
				[
					"Locator Bar",
					"Show Coordinates",
					"Immediate Respawn",
					"Autosave Interval",
				],
			)
		]);

		return match($choice){
			"Locator Bar" =>
			new EditRuleOptions($this->plugin, $this->player, "locator-bar", "Locator Bar"),
			"Show Coordinates" =>
			new EditRuleOptions($this->plugin, $this->player, "show-coordinates", "Show Coordinates"),
			"Immediate Respawn" =>
			new EditRuleOptions($this->plugin, $this->player, "do-immediate-respawn", "Immediate Respawn"),
			"Autosave Interval" =>
			new EditAutosaveOptions($this->plugin, $this->player, "Autosave Interval Edit"),
			default => null,
		};
	}

	protected function userDispose() : void{
		unset($this->plugin, $this->player);
	}
}
