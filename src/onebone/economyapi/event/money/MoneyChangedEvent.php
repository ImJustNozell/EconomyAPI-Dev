<?php


namespace onebone\economyapi\event\money;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\EconomyAPIEvent;

class MoneyChangedEvent extends EconomyAPIEvent
{
	private $username, $money;
	public static $handlerList;

	public function __construct(EconomyAPI $plugin, $username, $money, $issuer)
	{
		parent::__construct($plugin, $issuer);
		$this->username = $username;
		$this->money = $money;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getMoney()
	{
		return $this->money;
	}
}
