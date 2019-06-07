<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Struct;

class BasketOverview extends Base {
	public $customerId;
	public $isGuest;
	public $numberOfOrders = 0;
	public $registrationDate;
	public $currency;
	public $amount;
	public $amountNet;
	public $vat;
}