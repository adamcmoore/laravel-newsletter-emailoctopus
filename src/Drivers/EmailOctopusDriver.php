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
        array $properties = [],
        string $listName = '',
        array $options = []
    ) {
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $properties, $options);

		return $this->emailOctopus->lists()->createContact($list->getId(), $options);
    }

    public function subscribePending(string $email, array $properties = [], string $listName = '', array $options = [])
    {
        $options = array_merge($options, ['status' => 'pending']);

        return $this->subscribe($email, $properties, $listName, $options);
    }

    public function subscribeOrUpdate(
        string $email,
        array $properties = [],
        string $listName = '',
        array $options = []
    ) {
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $properties, $options);

		return $this->emailOctopus->lists()->updateContact($list->getId(), $this->getSubscriberHash($email), $options);
    }

    public function getMembers(string $listName = '', array $parameters = [])
    {
        $list = $this->lists->findByName($listName);

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

    protected function getSubscriptionOptions(string $email, array $mergeFields, array $options): array
    {
        $defaultOptions = [
            'email_address' => $email,
            'status' => 'SUBSCRIBED',
        ];

        if (count($mergeFields)) {
            $defaultOptions['merge_fields'] = $mergeFields;
        }

		return array_merge($defaultOptions, $options);
    }

    protected function getSubscriberHash(string $email): string
    {
        return md5($email);
    }
}
