<?php
namespace AcMoore\LaravelNewsletter\Tests;


use AcMoore\LaravelNewsletter\Drivers\EmailOctopusDriver;
use Illuminate\Support\Collection;
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


    public function testEmailOctopusGetList(): void
    {
		$newsletter = $this->makeNewsletter();

		$result = $newsletter->getList('default');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertArrayHasKey('double_opt_in', $result);
    }


    public function testEmailOctopusDriverCanSubscribe(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		$this->assertFalse($newsletter->isSubscribed($email_address));
		$this->assertFalse($newsletter->hasMember($email_address));

		$result = $newsletter->subscribe($email_address);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);

		$this->assertTrue($newsletter->isSubscribed($email_address));
		$this->assertTrue($newsletter->hasMember($email_address));
    }


    public function testEmailOctopusDriverCanSubscribeWithFields(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';
		$fields = [
			'FirstName' => 'Tessa',
			'LastName'  => 'McTest',
		];

		$result = $newsletter->subscribe(
			$email_address,
			$fields,
			'default'
		);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);
		$this->assertEquals($fields, $result['fields']);
    }


    public function testEmailOctopusDriverCanSubscribeWithTags(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';
		$tags = [
			'tag1' ,
			'tag2',
		];

		$result = $newsletter->subscribe(
			$email_address,
			[],
			'default',
			$tags
		);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);
		$this->assertEquals($tags, $result['tags']);
    }


    public function testEmailOctopusDriverCanSubscribePending(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		if (!$newsletter->isDoubleOptin('default')) {
			$this->fail('List is not set as Double opt-in');
		}

		$result = $newsletter->subscribePending($email_address);

		$this->assertIsArray($result);

		$this->assertEquals('PENDING', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);
    }


    public function testEmailOctopusDriverCanSubscribeOrUpdate(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		$result = $newsletter->subscribeOrUpdate($email_address);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);


		$fields = [
			'FirstName' => 'Tessa',
			'LastName'  => 'McTest',
		];

		$result = $newsletter->subscribeOrUpdate($email_address, $fields);
		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);
		$this->assertEquals($fields, $result['fields']);
    }


    public function testEmailOctopusDriverCanUnsubscribe(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		$result = $newsletter->subscribeOrUpdate($email_address);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);

		$this->assertTrue($newsletter->hasMember($email_address));
		$this->assertTrue($newsletter->isSubscribed($email_address));

		$result = $newsletter->unsubscribe($email_address);
		$this->assertIsArray($result);
		$this->assertEquals('UNSUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);

		$this->assertTrue($newsletter->hasMember($email_address));
		$this->assertFalse($newsletter->isSubscribed($email_address));
    }


    public function testEmailOctopusDriverCanDelete(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		$result = $newsletter->subscribe($email_address);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);


		$result = $newsletter->delete($email_address);
		$this->assertIsArray($result);

		$this->assertFalse($newsletter->hasMember($email_address));
    }


    public function testEmailOctopusDriverCanGetMember(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address = uniqid().'@test.org';

		$result = $newsletter->subscribe($email_address);

		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);


		$result = $newsletter->getMember($email_address);
		$this->assertIsArray($result);
		$this->assertEquals('SUBSCRIBED', $result['status']);
		$this->assertEquals($email_address, $result['email_address']);
    }


    public function testEmailOctopusDriverCanGetMembers(): void
    {
		$newsletter = $this->makeNewsletter();
		$email_address1 = uniqid().'@test.org';
		$email_address2 = uniqid().'@test.org';

		$newsletter->subscribe($email_address1);
		$newsletter->subscribe($email_address2);

		$result = $newsletter->getMembers();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('data', $result);

		$list = new Collection($result['data']);
		$list = $list->keyBy('email_address');
		$this->assertTrue($list->has($email_address1));
		$this->assertTrue($list->has($email_address2));
    }
}