<?php

declare(strict_types=1);

namespace DaisukeDaisuke\GameRuleToggler\rule;

final class ShowCoordinatesRule extends AbstractBoolRule{
	protected function getRuleName() : string{ return "showcoordinates"; }
	protected function getCommandKey() : string{ return "showCoordinates"; }
}