<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthLoginTest extends WebTestCase
{
	/**
	 * @var User $validUser
	 */
	private $validUser;

	protected function setUp(): void
	{
		parent::setUp();

		$email = 'testuser@local.lcoal';
		$password = hash('sha256', '12345678'.'taskoo7312');

		$this->validUser = new User();
		$this->validUser
			->setEmail($email)
			->setPassword($password)
			->setFirstname("Test")
			->setLastname("User")
			->setActive(true);

		// TODO setup valid test user in database

	}

	protected function tearDown(): void
	{
		parent::tearDown();

		// TODO delete test user in database

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
				'username' => $this->validUser->getEmail(),
				'password' => $this->validUser->getPassword()
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
