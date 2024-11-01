<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class SetMoneyCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("setmoney");
		parent::__construct("setmoney", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.setmoney");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		$player = array_shift($params);
		$amount = array_shift($params);

		if (!is_numeric($amount)) {
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if (($p = EconomyAPI::getInstance()->getServer()->getPlayerByPrefix($player)) instanceof Player) {
			$player = $p->getName();
		}

		$result = EconomyAPI::getInstance()->setMoney($player, $amount);
		switch ($result) {
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("setmoney-invalid-number", [$amount], $sender->getName()));
				break;
			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("player-never-connected", [$player], $sender->getName()));
				break;
			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("setmoney-failed", [], $sender->getName()));
				break;
			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("setmoney-setmoney", [$player, $amount], $sender->getName()));

				if ($p instanceof Player) {
					$p->sendMessage(EconomyAPI::getInstance()->getMessage("setmoney-set", [$amount], $p->getName()));
				}
				break;
			default:
				$sender->sendMessage("WTF");
		}
		return true;
	}
}
