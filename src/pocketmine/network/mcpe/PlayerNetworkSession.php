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

namespace pocketmine\network\mcpe;


use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Timings;
use pocketmine\network\mcpe\handler\NetworkHandler;
use pocketmine\network\mcpe\handler\SimpleNetworkHandler;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;
use pocketmine\Server;

class PlayerNetworkSession{

	/** @var Server */
	private $server;
	/** @var Player */
	private $player;

	/** @var NetworkHandler */
	private $handler;

	public function __construct(Server $server, Player $player){
		$this->server = $server;
		$this->player = $player;

		$this->handler = new SimpleNetworkHandler($player);
	}

	public function handleDataPacket(DataPacket $packet) : void{
		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		$packet->decode();
		if(!$packet->feof() and !$packet->mayHaveUnreadBytes()){
			$remains = substr($packet->buffer, $packet->offset);
			$this->server->getLogger()->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": 0x" . bin2hex($remains));
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this->player, $packet));
		if(!$ev->isCancelled() and !$this->handler->handleDataPacket($packet)){
			$this->server->getLogger()->debug("Unhandled " . $packet->getName() . " received from " . $this->player->getName() . ": 0x" . bin2hex($packet->buffer));
		}

		$timings->stopTiming();
	}

	public function getHandler() : NetworkHandler{
		return $this->handler;
	}
}
