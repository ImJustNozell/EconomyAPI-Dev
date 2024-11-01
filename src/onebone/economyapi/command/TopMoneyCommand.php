<?php


namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\task\SortTask;

class TopMoneyCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("topmoney");
		parent::__construct("topmoney", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.topmoney");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) return false;

		$page = (int)array_shift($params);

		$server = EconomyAPI::getInstance()->getServer();

		$banned = [];
		foreach ($server->getNameBans()->getEntries() as $entry) {
			if (EconomyAPI::getInstance()->accountExists($entry->getName())) {
				$banned[] = $entry->getName();
			}
		}
		$ops = [];
		foreach ($server->getOps()->getAll() as $op) {
			if (EconomyAPI::getInstance()->accountExists($op)) {
				$ops[] = $op;
			}
		}

		$task = new SortTask($sender->getName(), EconomyAPI::getInstance()->getAllMoney(), EconomyAPI::getInstance()->getConfig()->get("add-op-at-rank"), $page, $ops, $banned);
		$server->getAsyncPool()->submitTask($task);
		return true;
	}
}
