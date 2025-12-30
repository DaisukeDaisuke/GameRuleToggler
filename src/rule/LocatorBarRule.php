<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler\rule;

final class LocatorBarRule extends AbstractBoolRule{
	protected function getRuleName() : string{ return "locatorBar"; }
	protected function getCommandKey() : string{ return "locatorBar"; }
}