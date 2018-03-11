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

namespace pocketmine\event;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\RegisteredListener;

class HandlerList{
	/** @var string */
	private $class;

	/**
	 * @var RegisteredListener[][]
	 */
	private $handlerSlots = [];

	/**
	 * @var HandlerList[]
	 */
	private static $allLists = [];

	/**
	 * @var HandlerList[][] a 2D array of HandlerList.
	 *                      The first dimension index is the class name, containing an array of HandlerList for that class and its superclasses.
	 *                      The second dimension index is an integer >= 0, starting from the HandlerList for that class.
	 */
	private static $classMap = [];

	/**
	 * Unregisters all the listeners
	 * If a Plugin or Listener is passed, all the listeners with that object will be removed
	 *
	 * @param Plugin|Listener|null $object
	 */
	public static function unregisterAll($object = null) : void{
		if($object instanceof Listener or $object instanceof Plugin){
			foreach(self::$allLists as $h){
				$h->unregister($object);
			}
		}else{
			foreach(self::$allLists as $h){
				foreach($h->handlerSlots as $key => $list){
					$h->handlerSlots[$key] = [];
				}
			}
		}
	}

	public function __construct(string $class){
		$this->class = $class;
		$this->handlerSlots = array_fill_keys(EventPriority::ALL, []);
		self::$allLists[] = $this;
	}

	/**
	 * Returns the HandlerList for listeners that explicitly handle this event.
	 *
	 * Calling this method also lazily initializes the $classMap inheritance tree of handler lists.
	 *
	 * @param string $event
	 * @return null|HandlerList
	 * @throws \ReflectionException
	 */
	public static function getHandlerListFor(string $event) : ?HandlerList{
		if(isset(self::$classMap[$event])){
			return self::$classMap[$event][0];
		}

		$class = new \ReflectionClass($event);
		$tags = PluginManager::parseDocComment((string) $class->getDocComment());
		$noHandle = $class->isAbstract() && !(isset($tags["allowHandle"]) && $tags["allowHandle"] !== "false");

		$super = $class;
		$parentList = null;
		while($parentList === null && ($super = $super->getParentClass()) !== false){
			// skip $noHandle events in the inheritance tree to go to the nearest ancestor
			// while loop to allow skipping $noHandle events in the inheritance tree
			$parentList = self::getHandlerListFor($super->getName());
		}
		$lists = $parentList !== null ? self::$classMap[$parentList->class] : [];

		$list = $noHandle ? null : new HandlerList($event) ;
		array_unshift($lists, $list);

		self::$classMap[$event] = $lists;
		return $list;
	}

	/**
	 * @param string $event
	 * @return HandlerList[]
	 */
	public static function getHandlerListsFor(string $event) : array{
		return self::$classMap[$event] ?? [];
	}

	/**
	 * @param RegisteredListener $listener
	 *
	 * @throws \Exception
	 */
	public function register(RegisteredListener $listener) : void{
		if(!in_array($listener->getPriority(), EventPriority::ALL, true)){
			return;
		}
		if(isset($this->handlerSlots[$listener->getPriority()][spl_object_hash($listener)])){
			throw new \InvalidStateException("This listener is already registered to priority {$listener->getPriority()} of event {$this->class}");
		}
		$this->handlerSlots[$listener->getPriority()][spl_object_hash($listener)] = $listener;
	}

	/**
	 * @param RegisteredListener[] $listeners
	 */
	public function registerAll(array $listeners) : void{
		foreach($listeners as $listener){
			$this->register($listener);
		}
	}

	/**
	 * @param RegisteredListener|Listener|Plugin $object
	 */
	public function unregister($object) : void{
		if($object instanceof Plugin or $object instanceof Listener){
			foreach($this->handlerSlots as $priority => $list){
				foreach($list as $hash => $listener){
					if(($object instanceof Plugin and $listener->getPlugin() === $object)
						or ($object instanceof Listener and $listener->getListener() === $object)
					){
						unset($this->handlerSlots[$priority][$hash]);
					}
				}
			}
		}elseif($object instanceof RegisteredListener){
			if(isset($this->handlerSlots[$object->getPriority()][spl_object_hash($object)])){
				unset($this->handlerSlots[$object->getPriority()][spl_object_hash($object)]);
			}
		}
	}

	/**
	 * @param int $priority
	 * @return RegisteredListener[]
	 */
	public function getListenersByPriority(int $priority) : array{
		return $this->handlerSlots[$priority];
	}

	/**
	 * @return HandlerList[]
	 */
	public static function getHandlerLists() : array{
		return self::$allLists;
	}
}
