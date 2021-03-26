<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserRights;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DummyUserFixture extends Fixture
{
	public const EMAIL = 'dummy@local.local';
	public const PASSWORD = '12345678';
	public const FIRSTNAME = 'DUMMY';
	public const LASTNAME = 'USER';

    public function load(ObjectManager $manager)
    {
	    $hashedPassword = hash('sha256', self::PASSWORD.'taskoo7312');

	    $user = new User();
	    $user
		    ->setEmail(self::EMAIL)
		    ->setPassword($hashedPassword)
		    ->setFirstname(self::FIRSTNAME)
		    ->setLastname(self::LASTNAME)
		    ->setActive(true);

	    $userRights = new UserRights();
	    $userRights->setDefaults($user);

	    $manager->persist($userRights);
        $manager->persist($user);
        $manager->flush();
    }
}
