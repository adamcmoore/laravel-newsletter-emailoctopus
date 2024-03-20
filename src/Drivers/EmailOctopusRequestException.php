<?php

namespace AcMoore\LaravelNewsletter\Drivers;

use Throwable;


class EmailOctopusRequestException extends \Exception
{
	const MEMBER_NOT_FOUND = 'MEMBER_NOT_FOUND';
	const INVALID_PARAMETERS = 'INVALID_PARAMETERS';

	protected string $email_octopus_code;

	public function __construct(string $message, string $email_octopus_code, ?Throwable $previous = null)
	{
		$this->email_octopus_code = $email_octopus_code;
		parent::__construct($message, 0, $previous);
	}

	public function getEmailOctopusCode(): string
	{
		return $this->email_octopus_code;
	}
}