<?php


namespace onebone\economyapi\event\money;

use onebone\economyapi\event\EconomyAPIEvent;
use onebone\economyapi\EconomyAPI;

class PayMoneyEvent extends EconomyAPIEvent
{
	private $payer, $target, $amount;
	public static $handlerList;

	public function __construct(EconomyAPI $plugin, $payer, $target, $amount)
	{
		parent::__construct($plugin, "PayCommand");

		$this->payer = $payer;
		$this->target = $target;
		$this->amount = $amount;
	}

	public function getPayer()
	{
		return $this->payer;
	}

	public function getTarget()
	{
		return $this->target;
	}

	public function getAmount()
	{
		return $this->amount;
	}
}
