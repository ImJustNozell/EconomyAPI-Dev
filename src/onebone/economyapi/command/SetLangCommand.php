<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\Command\CommandSender;
use pocketmine\utils\TextFormat;

use onebone\economyapi\EconomyAPI;

class SetLangCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("setlang");
		parent::__construct("setlang", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.setlang");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		$lang = array_shift($params);
		if (trim($lang) === "") {
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if (EconomyAPI::getInstance()->setPlayerLanguage($sender->getName(), $lang)) {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("language-set", [$lang], $sender->getName()));
		} else {
			$sender->sendMessage(TextFormat::RED . "There is no language such as $lang");
		}
		return true;
	}
}
