<?php


namespace onebone\economyapi\task;

use onebone\economyapi\EconomyAPI;

use pocketmine\scheduler\Task;

class SaveTask extends Task
{
	private $plugin;
	public function __construct(EconomyAPI $plugin)
	{
		$this->plugin = $plugin;
	}

	public function onRun(): void
	{
		$this->plugin->saveAll();
	}
}
