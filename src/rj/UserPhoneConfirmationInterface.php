<?php namespace Rj;

interface UserPhoneConfirmationInterface {

	/** @return int */
	public function getId();

	/** @return string */
	public function getPhoneNumber();

	public function setPhoneNumberConfirmed($value);

}

