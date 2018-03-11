<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\level;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SpawnProtectionZone implements ProtectedZone{
	/** @var Level */
	private $level;

	public function __construct(Level $level){
		$this->level = $level;
	}

	public function isProtected(Player $player, Vector3 $actionPos) : bool{
		if(!$player->hasPermission("pocketmine.spawnprotect.bypass") and ($distance = $this->level->getServer()->getSpawnRadius()) > -1){
			$t = new Vector2($actionPos->x, $actionPos->z);

			$spawnLocation = $this->level->getSpawnLocation();
			$s = new Vector2($spawnLocation->x, $spawnLocation->z);
			if(count($this->level->getServer()->getOps()->getAll()) > 0 and $t->distance($s) <= $distance){
				return true;
			}
		}

		return false;
	}

}
