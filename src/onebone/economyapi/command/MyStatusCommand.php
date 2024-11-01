<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class MyStatusCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("mystatus");
		parent::__construct("mystatus", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.mystatus");
	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
		}

		$money = EconomyAPI::getInstance()->getAllMoney();

		$allMoney = 0;
		foreach ($money as $m) {
			$allMoney += $m;
		}
		$topMoney = 0;
		if ($allMoney > 0) {
			$topMoney = round((($money[strtolower($sender->getName())] / $allMoney) * 100), 2);
		}

		$sender->sendMessage(EconomyAPI::getInstance()->getMessage("mystatus-show", [$topMoney], $sender->getName()));
		return true;
	}
}
