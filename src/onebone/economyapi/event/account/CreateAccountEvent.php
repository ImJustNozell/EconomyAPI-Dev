<?php


namespace onebone\economyapi\event\account;

use onebone\economyapi\event\EconomyAPIEvent;
use onebone\economyapi\EconomyAPI;

class CreateAccountEvent extends EconomyAPIEvent
{
	private $username, $defaultMoney;
	public static $handlerList;

	public function __construct(EconomyAPI $plugin, $username, $defaultMoney, $issuer)
	{
		parent::__construct($plugin, $issuer);
		$this->username = $username;
		$this->defaultMoney = $defaultMoney;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function setDefaultMoney($money)
	{
		$this->defaultMoney = $money;
	}

	public function getDefaultMoney()
	{
		return $this->defaultMoney;
	}
}
