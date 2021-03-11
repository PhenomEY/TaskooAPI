<?php declare(strict_types=1);

namespace App\Api;

use App\Entity\Color;
use App\Entity\Notifications;
use App\Entity\Organisations;
use App\Entity\Projects;
use App\Entity\Settings;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Entity\User;
use App\Security\TaskooAuthenticator;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskooApiController extends AbstractController
{
    protected $authenticator;

    protected $responseManager;

    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
    }

    protected function projectsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Projects::class);
    }

    protected function taskGroupsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(TaskGroups::class);
    }

    protected function tasksRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Tasks::class);
    }

    protected function notificationsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Notifications::class);
    }

    protected function organisationsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Organisations::class);
    }

    protected function userRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(User::class);
    }

    protected function settingsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Settings::class);
    }

    protected function colorsRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Color::class);
    }

}