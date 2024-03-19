<?php
namespace AcMoore\LaravelNewsletter\Tests;


use Dotenv\Dotenv;

class TestCase extends \PHPUnit\Framework\TestCase
{

	public function setUp(): void
	{
		parent::setUp();

		$dotenv = Dotenv::createImmutable(__DIR__);
		$dotenv->load();
	}
}