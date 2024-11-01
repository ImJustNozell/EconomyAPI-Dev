<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class MyMoneyCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("mymoney");
		parent::__construct("mymoney", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.mymoney");
	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		if ($sender instanceof Player) {
			$money = EconomyAPI::getInstance()->myMoney($sender);
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("mymoney-mymoney", [$money]));
			return true;
		}
		$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
		return true;
	}
}
