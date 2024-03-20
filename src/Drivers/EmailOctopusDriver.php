<?php

namespace AcMoore\LaravelNewsletter\Drivers;

use GoranPopovic\EmailOctopus\Client;
use GoranPopovic\EmailOctopus\EmailOctopus;
use Spatie\Newsletter\Drivers\Driver;
use Spatie\Newsletter\Exceptions\InvalidNewsletterList;
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


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function subscribe(
        string $email,
        array $fields = [],
        string $listName = '',
        array $tags = []
    ): array
	{
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $fields, $tags);

		$response = $this->emailOctopus->lists()->createContact($list->getId(), $options);

		return $this->handleResponse($response);
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function subscribePending(
		string $email,
		array $fields = [],
		string $listName = '',
		array $tags = []
	): array
	{
		$list = $this->lists->findByName($listName);

		$options = $this->getSubscriptionOptions($email, $fields, $tags);

		if ($this->isDoubleOptin($listName)) {
			$options['status'] = 'PENDING';
		}

		$response = $this->emailOctopus->lists()->createContact($list->getId(), $options);

		return $this->handleResponse($response);
	}


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function subscribeOrUpdate(
        string $email,
        array $fields = [],
        string $listName = '',
        array $tags = []
    ): array
	{
        $list = $this->lists->findByName($listName);

        $options = $this->getSubscriptionOptions($email, $fields, $tags);

		if ($this->hasMember($email, $listName)) {
			$response = $this->emailOctopus->lists()->updateContact($list->getId(), $this->getSubscriberHash($email), $options);
		} else {
			$response = $this->emailOctopus->lists()->createContact($list->getId(), $options);
		}

		return $this->handleResponse($response);
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function unsubscribe(string $email, string $listName = ''): array
	{
        $list = $this->lists->findByName($listName);

		$response = $this->emailOctopus->lists()->updateContact($list->getId(), $this->getSubscriberHash($email), [
			'status' => 'UNSUBSCRIBED',
		]);

		return $this->handleResponse($response);
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function delete(string $email, string $listName = ''): array
	{
        $list = $this->lists->findByName($listName);

		$response = $this->emailOctopus->lists()->deleteContact($list->getId(), $this->getSubscriberHash($email));

		return $this->handleResponse($response);
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function getMembers(string $listName = '', array $parameters = []): array
	{
		$list = $this->lists->findByName($listName);

		$parameters = array_merge($parameters, ['limit' => 100]);

		$response = $this->emailOctopus->lists()->getAllContacts($list->getId(), $parameters);

		return $this->handleResponse($response);
	}


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function getMember(string $email, string $listName = ''): array
	{
		$list = $this->lists->findByName($listName);

		$response = $this->emailOctopus->lists()->getContact($list->getId(), $this->getSubscriberHash($email));

		return $this->handleResponse($response);
	}


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function hasMember(string $email, string $listName = ''): bool
    {
		try {
			$response = $this->getMember($email, $listName);
		} catch (EmailOctopusRequestException $e) {
			if ($e->getEmailOctopusCode() === EmailOctopusRequestException::MEMBER_NOT_FOUND) {
				return false;
			}
			throw $e;
		}

        if (!isset($response['email_address'])) {
            return false;
        }

        if (strtolower($response['email_address']) != strtolower($email)) {
            return false;
        }

        return true;
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function isSubscribed(string $email, string $listName = ''): bool
    {
		try {
			$response = $this->getMember($email, $listName);
		} catch (EmailOctopusRequestException $e) {
			if ($e->getEmailOctopusCode() === 'MEMBER_NOT_FOUND') {
				return false;
			}
			throw $e;
		}


        if ($response['status'] !== 'SUBSCRIBED') {
            return false;
        }

        return true;
    }


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
	public function getList(string $listName): array
	{
		$list = $this->lists->findByName($listName);

		$response = $this->emailOctopus->lists()->get($list->getId());

		return $this->handleResponse($response);
	}


	/**
	 * @throws EmailOctopusRequestException
	 * @throws InvalidNewsletterList
	 */
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


	/**
	 * @throws EmailOctopusRequestException
	 */
	protected function handleResponse(array $response): array
	{
		if (array_key_exists('error', $response)) {
			$code = $response['error']['code'];
			$message = $response['error']['message'];
			throw new EmailOctopusRequestException($message, $code);
		}

		return $response;
	}
}
