<?php declare(strict_types=1);

namespace App\Fixtures;

use App\Entity\Color;
use App\Entity\Settings;
use App\Entity\Team;
use App\Entity\Projects;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\UserPermissions;
use App\Security\TaskooAuthenticator;
use App\Service\TaskooColorService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class Defaults extends Fixture
{
    private $authenticator;

    private $colorService;

    public function __construct(TaskooAuthenticator $authenticator, TaskooColorService $colorService)
    {
        $this->authenticator = $authenticator;
        $this->colorService = $colorService;
    }

    public function load(ObjectManager $manager)
    {
        $settings = new Settings();
        $settings->setAppUrl('https://app.taskoo.de');
        $settings->setMailSender('Taskoo <noreply@taskoo.de>');
        $manager->persist($settings);

        $color1 = new Color();
        $color1->setHexCode('#c8ae1e');
        $manager->persist($color1);

        $color2 = new Color();
        $color2->setHexCode('#1e66c8');
        $manager->persist($color2);

        $color3 = new Color();
        $color3->setHexCode('#a51e47');
        $manager->persist($color3);

        $color4 = new Color();
        $color4->setHexCode('#009500');
        $manager->persist($color4);

        $color5 = new Color();
        $color5->setHexCode('#9c1ea4');
        $manager->persist($color5);

        $manager->flush();

        $team = new Team();
        $team->setName('My Team');
        $team->setColor($this->colorService->getRandomColor());
        $manager->persist($team);

        $project = new Projects();
        $project->setName('My Project');
        $project->setDescription('This is your first project on taskoo! :-)');
        $project->setTeam($team);
        $manager->persist($project);

        $group = new TaskGroups();
        $group->setName('Put your tasks here!');
        $group->setProject($project);
        $group->setPosition(0);
        $manager->persist($group);

        $task = new Tasks();
        $task->setName('this is your first task!');
        $task->setPosition(0);
        $task->setTaskGroup($group);
        $manager->persist($task);
        $manager->flush();

        $user = new User();
        $user->setFirstname('Admin');
        $user->setLastname('Jackson');
        $user->setEmail('admin@taskoo.de');
        $hashedPassword = $this->authenticator->generatePassword('admin123');
        $user->setPassword($hashedPassword);
        $user->setColor($this->colorService->getRandomColor());
        $user->setActive(true);

        $manager->persist($user);
        $manager->flush();

        $permissions = new UserPermissions();
        $permissions->setAdministration(true);
        $permissions->setUser($user);

        $manager->persist($permissions);
        $manager->flush();
    }
}
