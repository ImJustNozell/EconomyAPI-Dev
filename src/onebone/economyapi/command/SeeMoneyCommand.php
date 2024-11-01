<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class SeeMoneyCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("seemoney");
		parent::__construct("seemoney", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.seemoney");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		$player = array_shift($params);
		if (trim($player) === "") {
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if (($p = EconomyAPI::getInstance()->getServer()->getPlayerByPrefix($player)) instanceof Player) {
			$player = $p->getName();
		}

		$money = EconomyAPI::getInstance()->myMoney($player);
		if ($money !== false) {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("seemoney-seemoney", [$player, $money], $sender->getName()));
		} else {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("player-never-connected", [$player], $sender->getName()));
		}
		return true;
	}
}
