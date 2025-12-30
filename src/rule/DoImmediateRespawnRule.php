<?php

declare(strict_types=1);


namespace DaisukeDaisuke\GameRuleToggler\rule;

final class DoImmediateRespawnRule extends AbstractBoolRule{
	protected function getRuleName() : string{ return "doImmediateRespawn"; }
	protected function getCommandKey() : string{ return "doImmediateRespawn"; }
}