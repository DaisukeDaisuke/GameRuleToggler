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
use pocketmine\scheduler\ClosureTask;
use pocketmine\player\Player;
use DaisukeDaisuke\GameRuleToggler\form\PlayerSettingsOptions;
use DaisukeDaisuke\AwaitFormOptions\exception\AwaitFormOptionsParentException;
use DaisukeDaisuke\GameRuleToggler\form\AdminSetAllOptions;
use pocketmine\form\FormValidationException;
use DaisukeDaisuke\AwaitFormOptions\AwaitFormOptions;
use SOFe\AwaitGenerator\Await;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use cosmicpe\awaitform\AwaitForm;
use DaisukeDaisuke\GameRuleToggler\form\ConfigOptions;
use DaisukeDaisuke\AwaitFormOptions\FormOptions;

final class Main extends PluginBase implements Listener{

	/** @var AbstractBoolRule[] */
	private array $rules = [];
	private Setting $setting;

	private Config $locatorBarConfig;
	private Config $showCoordinatesConfig;
	private Config $doImmediateRespawnConfig;

	protected function onEnable() : void{
		$this->saveDefaultConfig();
		$this->setting = new Setting($this->getConfig());

		// AwaitForm の登録（README 指示に沿う）
		if(!AwaitForm::isRegistered()){
			AwaitForm::register($this);
		}

		$autosave = $this->setting->getAutosaveInterval();
		if($autosave > 0){
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
				foreach($this->rules as $rule){
					$rule->save();
				}
			}), $autosave * 20);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->locatorBarConfig = new Config(Path::join($this->getDataFolder(), "LocatorBar.json"), Config::JSON);
		$this->showCoordinatesConfig = new Config(Path::join($this->getDataFolder(), "showCoordinates.json"), Config::JSON);
		$this->doImmediateRespawnConfig = new Config(Path::join($this->getDataFolder(), "doImmediateRespawn.json"), Config::JSON);

		$this->reloadSetting();
	}

	public function regenerationRules() : void{
		$this->rules = [
			"locatorBar" => new LocatorBarRule(
				$this->locatorBarConfig,
				$this->setting->isLocatorBarForce(),
				$this->setting->isLocatorBarForceOpOnly(),
				$this->setting->getLocatorBarForcedValue(),
				$this->setting->getLocatorBarDefault()
			),
			"showcoordinates" => new ShowCoordinatesRule(
				$this->showCoordinatesConfig,
				$this->setting->isShowCoordinatesForce(),
				$this->setting->isShowCoordinatesForceOpOnly(),
				$this->setting->getShowCoordinatesForcedValue(),
				$this->setting->getShowCoordinatesDefault()
			),
			"doImmediateRespawn" => new DoImmediateRespawnRule(
				$this->doImmediateRespawnConfig,
				$this->setting->isDoImmediateRespawnForce(),
				$this->setting->isDoImmediateRespawnForceOpOnly(),
				$this->setting->getDoImmediateRespawnForcedValue(),
				$this->setting->getDoImmediateRespawnDefault()
			),
		];
	}

	// getter (フォーム等から参照するため)
	public function getLocatorRule() : AbstractBoolRule{
		return $this->rules['locatorBar'];
	}
	public function getShowCoordinatesRule() :AbstractBoolRule{
		return $this->rules['showcoordinates'];
	}
	public function getDoImmediateRespawnRule() : AbstractBoolRule{
		return $this->rules['doImmediateRespawn'];
	}

	/**
	 * Setting を新しく読み直す（不変オブジェクトを再生成する）
	 */
	public function reloadSetting() : void{
		$this->setting = new Setting($this->getConfig());
		$this->regenerationRules();
	}

	public function getSetting() : Setting{
		return $this->setting;
	}


	// プレイヤー個別の保存値を上書きして、オンラインなら即適用する
	public function setPlayerSettingAndApply(string $playerName, string $ruleKey, bool $value, ?Player $onlinePlayer = null) : void{
		if(!isset($this->rules[$ruleKey])){
			return;
		}
		$rule = $this->rules[$ruleKey];

		// global force が true の場合、個別設定は書き換え不可
		if($rule->isForce()){
			// ここはルール内の isForce() を実装して返すようにしておく
			return;
		}

		// op-only チェック: 管理者フォームからの上書きには呼び出し側で権限チェックを入れてください
		$rule->setPlayerSavedValue($playerName, $value);

		// オンラインなら即時適用
		if($onlinePlayer !== null){
			$rule->applyToPlayer($onlinePlayer, $value, true);
		}else{
			// オフラインなら何もしない（保存されているので次回ログイン時に反映）
		}
	}

	// setall 実行
	public function setAllRule(string $ruleKey, bool $value, bool $clearSaved, ?Player $executedBy = null) : void{
		if(!isset($this->rules[$ruleKey])){
			return;
		}
		$rule = $this->rules[$ruleKey];

		// force は global の仕様なので、管理者が全員に送る用途としては許容（呼び出し元で権限チェック推奨）
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$rule->applyToPlayer($player, $value, false); // 送信して playerModifiable=false（強制送信）
		}

		if($clearSaved){
			$rule->clearAllSaved();
		}else{
			// すべてのプレイヤーの保存値を同じ値にする（必要なら）
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$rule->setPlayerSavedValue($player->getName(), $value);
			}
		}
	}


	// フォームを開く（例）
	public function openSettingsForm(Player $player) : void{
		Await::f2c(function() use ($player) : \Generator{
			try{
				yield from AwaitFormOptions::sendFormAsync(
					player: $player,
					title: "Settings",
					options: [
						new PlayerSettingsOptions($this, $player),
					]
				);
			}catch(FormValidationException|AwaitFormOptionsParentException){
				// フォームキャンセル等
			}
		});
	}

	public function openAdminSetAllForm(Player $player) : void{
		Await::f2c(function() use ($player) : \Generator{
			try{
				yield from AwaitFormOptions::sendFormAsync(
					player: $player,
					title: "Set All",
					options: [
						new AdminSetAllOptions($this, $player),
					]
				);
			}catch(FormValidationException|AwaitFormOptionsParentException){
			}
		});
	}

	/** 管理者向け：動的コンフィグフォーム（rtconfig という要求がありましたが、onCommand の label に合わせフォームは rtseting を使います） */
	public function openDynamicConfigForm(Player $player) : void{
		Await::f2c(function() use ($player) : \Generator{
			try{
				[$form] = yield from AwaitFormOptions::sendFormAsync(
					player: $player,
					title: "Server Config",
					options: [
						new ConfigOptions($this, $player),
					]
				);
				/** @var FormOptions $form */
				$form = $form["result"];
				while(true){
					[$result] = yield from AwaitFormOptions::sendFormAsync(
						player: $player,
						title: "Autosave Config",
						options: [
							clone $form,
						]
					);
					if($result["result"]){
						break;
					}
					$player->sendToastNotification("GameRuleTogglers", "エラーがあります！もう一度やり直してください！");
				}
			}catch(FormValidationException|AwaitFormOptionsParentException){
			}
		});
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		foreach($this->rules as $rule){
			$rule->onJoin($event->getPlayer());
		}
	}

	public function packetReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof SettingsCommandPacket){
			$player = $event->getOrigin()->getPlayer();
			if($player !== null){
				foreach($this->rules as $rule){
					if($rule->onSettingCommand($player, $packet->getCommand())){
						break;
					}
				}
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RED . "console cannot run this command");
			return true;
		}
		if($label === "rtseting"){
			$this->openSettingsForm($sender);
			return true;
		}
		if($label === "rtsetall"){
			if(!$this->getServer()->isOp($sender->getName())){
				return false;
			}
			$this->openAdminSetAllForm($sender);
			return true;
		}

		if($label === "rtconfig"){
			if(!$this->getServer()->isOp($sender->getName())){
				return false;
			}
			$this->openDynamicConfigForm($sender);
			return true;
		}

		return false;
	}


	public function onDisable() : void{
		foreach($this->rules as $rule){
			$rule->save();
		}
	}
}
