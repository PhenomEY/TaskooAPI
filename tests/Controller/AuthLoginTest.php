<?php

namespace App\Tests\Controller;

use App\DataFixtures\DummyUserFixture;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthLoginTest extends WebTestCase
{

	protected function setUp(): void
	{
		parent::setUp();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
	}

    public function testAuthLoginWithOutPayload(): void
    {
        static::createClient()->request(Request::METHOD_POST, '/auth/login');

	    $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

	public function testAuthLoginWithOutLogin(): void
	{
		$content = json_encode([
			// empty
		]);

		static::createClient()->request(Request::METHOD_POST, '/auth/login', [], [], [], $content);

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testAuthLoginWithOutLoginData(): void
	{
		$content = json_encode([
			'login' => [
				// empty
			]
		]);

		static::createClient()->request(Request::METHOD_POST, '/auth/login', [], [], [], $content);

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testAuthLoginWithBadPassword(): void
	{
		$content = json_encode([
			'login' => [
				'username' => 'invalid@invalid.invalid',
				/**
				 * hashedPassword returns null
				 * test with only [] this gives a warning
				 */
				'password' => ['invalid' => 0]
			]
		]);

		static::createClient()->request(Request::METHOD_POST, '/auth/login', [], [], [], $content);

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testAuthLoginWrongCredentials(): void
	{
		$content = json_encode([
			'login' => [
				'username' => 'invalid',
				'password' => 'invalid'
			]
		]);

		static::createClient()->request(Request::METHOD_POST, '/auth/login', [], [], [], $content);

		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testAuthLoginValidCredentials(): void
	{
		$content = json_encode([
			'login' => [
				'username' => DummyUserFixture::EMAIL,
				'password' => DummyUserFixture::PASSWORD
			]
		]);

		$client = static::createClient();
		$client->request(Request::METHOD_POST, '/auth/login', [], [], [], $content);

		$data = json_decode($client->getResponse()->getContent(), true);

		$this->assertResponseStatusCodeSame(Response::HTTP_OK);
		$this->assertSame(true, $data['success']);
		$this->assertSame('login_success', $data['message']);
	}
}
