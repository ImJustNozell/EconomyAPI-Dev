<?php


namespace onebone\economyapi\task;


use onebone\economyapi\EconomyAPI;
use pocketmine\scheduler\Task;

class MySQLPingTask extends Task
{
	private $mysql;

	private $plugin;

	public function __construct(EconomyAPI $plugin, \mysqli $mysql)
	{
		$this->plugin = $plugin;

		$this->mysql = $mysql;
	}

	public function onRun(): void
	{
		if (!$this->mysql->ping()) {
			$this->plugin->openProvider();
		}
	}
}
