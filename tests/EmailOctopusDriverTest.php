<?php
namespace AcMoore\LaravelNewsletter\Tests;


use AcMoore\LaravelNewsletter\Drivers\EmailOctopusDriver;
use Spatie\Newsletter\Support\Lists;

class EmailOctopusDriverTest extends TestCase
{

	private function makeNewsletter(): EmailOctopusDriver
	{
		$driver_class = EmailOctopusDriver::class;
		$arguments = [
			'api_key' => $_ENV['EMAILOCTOPUS_API_KEY'],
		];

		$lists = Lists::createFromConfig([
			'default_list_name' => 'default',
			'lists' => [
				'default' => [
					'id' => $_ENV['EMAILOCTOPUS_LIST'],
				],
			]
		]);

		return $driver_class::make($arguments, $lists);
	}


    public function testEmailOctopusDriver(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = 'adam@acmoore.co.uk';

		$this->assertFalse($newsletter->hasMember($email_address));

    }
}