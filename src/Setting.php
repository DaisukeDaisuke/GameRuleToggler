<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler;

use pocketmine\utils\Config;

final class Setting{
	private bool $locatorBarForce;
	private bool $locatorBarValue;
	private bool $locatorBarDefault;

	private bool $showCoordinatesForce;
	private bool $showCoordinatesValue;
	private bool $showCoordinatesDefault;

	private bool $doImmediateRespawnForce;
	private bool $doImmediateRespawnValue;
	private bool $doImmediateRespawnDefault;

	private bool $locatorBarForceOpOnly;
	private bool $showCoordinatesForceOpOnly;
	private bool $doImmediateRespawnForceOpOnly;

	private int $autosaveInterval;

	public function __construct(Config $config){
		$this->locatorBarForce   = $config->getNested("rules.locator-bar.force", false);
		$this->locatorBarValue   = $config->getNested("rules.locator-bar.value", true);
		$this->locatorBarDefault = $config->getNested("rules.locator-bar.default", true);
		$this->locatorBarForceOpOnly = $config->getNested("rules.locator-bar.force-op-only", false);

		$this->showCoordinatesForce   = $config->getNested("rules.show-coordinates.force", false);
		$this->showCoordinatesValue   = $config->getNested("rules.show-coordinates.value", true);
		$this->showCoordinatesDefault = $config->getNested("rules.show-coordinates.default", true);
		$this->showCoordinatesForceOpOnly = $config->getNested("rules.show-coordinates.force-op-only", false);

		$this->doImmediateRespawnForce   = $config->getNested("rules.do-immediate-respawn.force", false);
		$this->doImmediateRespawnValue   = $config->getNested("rules.do-immediate-respawn.value", true);
		$this->doImmediateRespawnDefault = $config->getNested("rules.do-immediate-respawn.default", true);
		$this->doImmediateRespawnForceOpOnly = $config->getNested("rules.do-immediate-respawn.force-op-only", false);

		$this->autosaveInterval = max(
			30,
			(int) $config->getNested("autosave.interval", 3600)
		);
	}

	/* ---- locator bar ---- */

	public function isLocatorBarForce() : bool{
		return $this->locatorBarForce;
	}

	public function getLocatorBarForcedValue() : bool{
		return $this->locatorBarValue;
	}

	public function getLocatorBarDefault() : bool{
		return $this->locatorBarDefault;
	}

	/* ---- show coordinates ---- */

	public function isShowCoordinatesForce() : bool{
		return $this->showCoordinatesForce;
	}

	public function getShowCoordinatesForcedValue() : bool{
		return $this->showCoordinatesValue;
	}

	public function getShowCoordinatesDefault() : bool{
		return $this->showCoordinatesDefault;
	}

	/* ---- do immediate respawn ---- */

	public function isDoImmediateRespawnForce() : bool{
		return $this->doImmediateRespawnForce;
	}

	public function getDoImmediateRespawnForcedValue() : bool{
		return $this->doImmediateRespawnValue;
	}

	public function getDoImmediateRespawnDefault() : bool{
		return $this->doImmediateRespawnDefault;
	}

	/* ---- autosave ---- */

	public function getAutosaveInterval() : int{
		return $this->autosaveInterval;
	}

	public function isLocatorBarForceOpOnly() : bool{
		return $this->locatorBarForceOpOnly;
	}

	public function isShowCoordinatesForceOpOnly() : bool{
		return $this->showCoordinatesForceOpOnly;
	}


	public function isDoImmediateRespawnForceOpOnly() : bool{
		return $this->doImmediateRespawnForceOpOnly;
	}
}
