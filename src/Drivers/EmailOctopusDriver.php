<?php

namespace AcMoore\LaravelNewsletter\Drivers;

use GoranPopovic\EmailOctopus\Client;
use GoranPopovic\EmailOctopus\EmailOctopus;
use Spatie\Newsletter\Drivers\Driver;
use Spatie\Newsletter\Support\Lists;


class EmailOctopusDriver implements Driver
{
    protected Lists $lists;

    protected Client $emailOctopus;

    public static function make(array $arguments, Lists $lists): EmailOctopusDriver
	{
        return new self($arguments, $lists);
    }

    public function __construct(array $arguments, Lists $lists)
    {
        $this->emailOctopus = EmailOctopus::client($arguments['api_key'] ?? '');

        $this->lists = $lists;
    }

    public function getApi(): Client
    {
        return $this->emailOctopus;
    }

    public function subscribe(
        string $email,
        array $fields = [],
        string $listName = '',
        array $tags = []
    ) {
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $fields, $tags);

		return $this->emailOctopus->lists()->createContact($list->getId(), $options);
    }

    public function subscribePending(
		string $email,
		array $fields = [],
		string $listName = '',
		array $tags = []
	) {
		$list = $this->lists->findByName($listName);

		$options = $this->getSubscriptionOptions($email, $fields, $tags);

		if ($this->isDoubleOptin($listName)) {
			$options['status'] = 'PENDING';
		}

		return $this->emailOctopus->lists()->createContact($list->getId(), $options);
	}

    public function subscribeOrUpdate(
        string $email,
        array $fields = [],
        string $listName = '',
        array $tags = []
    ) {
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $fields, $tags);

		if ($this->hasMember($email, $listName)) {
			return $this->emailOctopus->lists()->updateContact($list->getId(), $this->getSubscriberHash($email), $options);
		} else {
			return $this->emailOctopus->lists()->createContact($list->getId(), $options);
		}
    }

    public function getMembers(string $listName = '', array $parameters = [])
    {
        $list = $this->lists->findByName($listName);

		$parameters = array_merge($parameters, ['limit' => 100]);

        return $this->emailOctopus->lists()->getAllContacts($list->getId(), $parameters);
    }

    public function getMember(string $email, string $listName = '')
    {
        $list = $this->lists->findByName($listName);

        return $this->emailOctopus->lists()->getContact($list->getId(), $this->getSubscriberHash($email));
    }

    public function unsubscribe(string $email, string $listName = '')
    {
        $list = $this->lists->findByName($listName);

		return $this->emailOctopus->lists()->updateContact($list->getId(), $this->getSubscriberHash($email), [
			'status' => 'UNSUBSCRIBED',
		]);
    }

    public function delete(string $email, string $listName = '')
    {
        $list = $this->lists->findByName($listName);

		return $this->emailOctopus->lists()->deleteContact($list->getId(), $this->getSubscriberHash($email));
    }

    public function hasMember(string $email, string $listName = ''): bool
    {
        $response = $this->getMember($email, $listName);

        if (! isset($response['email_address'])) {
            return false;
        }

        if (strtolower($response['email_address']) != strtolower($email)) {
            return false;
        }

        return true;
    }

    public function isSubscribed(string $email, string $listName = ''): bool
    {
        $response = $this->getMember($email, $listName);

        if ($response['status'] !== 'SUBSCRIBED') {
            return false;
        }

        return true;
    }


	public function getList(string $listName): array
	{
		$list = $this->lists->findByName($listName);

		return $this->emailOctopus->lists()->get($list->getId());
	}


	public function isDoubleOptin(string $listName): bool
	{
		$list = $this->getList($listName);

		return (bool) $list['double_opt_in'];
	}


    protected function getSubscriptionOptions(string $email, array $fields, array $tags): array
    {
        return [
            'email_address' => $email,
            'status' => 'SUBSCRIBED',
            'tags' => $tags,
            'fields' => $fields,
        ];
    }

    protected function getSubscriberHash(string $email): string
    {
        return md5($email);
    }
}
