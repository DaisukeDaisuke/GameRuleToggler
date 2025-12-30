<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\utils\Config;
use DaisukeDaisuke\GameRuleToggler\rule\DoImmediateRespawnRule;
use DaisukeDaisuke\GameRuleToggler\rule\ShowCoordinatesRule;
use DaisukeDaisuke\GameRuleToggler\rule\LocatorBarRule;
use DaisukeDaisuke\GameRuleToggler\rule\AbstractBoolRule;
use Symfony\Component\Filesystem\Path;
use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;

final class Main extends PluginBase implements Listener{

	/** @var AbstractBoolRule[] */
	private array $rules = [];
	private Setting $setting;

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$this->setting = new Setting($this->getConfig());

		$this->rules = [
			new LocatorBarRule(
				new Config(Path::join($this->getDataFolder(), "LocatorBar.json"), Config::JSON),
				$this->setting->isLocatorBarForce(),
				$this->setting->isLocatorBarForceOpOnly(),
				$this->setting->getLocatorBarForcedValue(),
				$this->setting->getLocatorBarDefault()
			),
			new ShowCoordinatesRule(
				new Config(Path::join($this->getDataFolder(), "showCoordinates.json"), Config::JSON),
				$this->setting->isShowCoordinatesForce(),
				$this->setting->isShowCoordinatesForceOpOnly(),
				$this->setting->getShowCoordinatesForcedValue(),
				$this->setting->getShowCoordinatesDefault()
			),
			new DoImmediateRespawnRule(
				new Config(Path::join($this->getDataFolder(), "doImmediateRespawn.json"), Config::JSON),
				$this->setting->isDoImmediateRespawnForce(),
				$this->setting->isDoImmediateRespawnForceOpOnly(),
				$this->setting->getDoImmediateRespawnForcedValue(),
				$this->setting->getDoImmediateRespawnDefault()
			),
		];

		$autosave = $this->setting->getAutosaveInterval();
		if($autosave > 0){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void{
				foreach($this->rules as $rule){
					$rule->save();
				}
			}), $autosave * 20);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		foreach($this->rules as $rule){
			$rule->onJoin($event->getPlayer());
		}
	}

	public function packetReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof SettingsCommandPacket){
			$player = $event->getOrigin()?->getPlayer();
			if($player !== null){
				foreach($this->rules as $rule){
					if($rule->onSettingCommand($player, $packet->getCommand())){
						break;
					}
				}
			}
		}
	}

	public function onDisable() : void{
		foreach($this->rules as $rule){
			$rule->save();
		}
	}
}
