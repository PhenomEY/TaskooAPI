<?php declare(strict_types=1);

namespace Taskoo\Api;

use Taskoo\Entity\Color;
use Taskoo\Entity\Favorites;
use Taskoo\Entity\Media;
use Taskoo\Entity\Notifications;
use Taskoo\Entity\Team;
use Taskoo\Entity\Projects;
use Taskoo\Entity\Settings;
use Taskoo\Entity\TaskGroups;
use Taskoo\Entity\Tasks;
use Taskoo\Entity\TeamRole;
use Taskoo\Entity\User;
use Taskoo\Security\TaskooAuthenticator;
use Taskoo\Service\ColorService;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiController extends AbstractController
{
    protected $authenticator;

    protected $responseManager;

    protected $colorService;

    protected $serializer;

    public function __construct(TaskooAuthenticator $authenticator, ResponseManager $responseManager, ColorService $colorService)
    {
        $this->authenticator = $authenticator;
        $this->responseManager = $responseManager;
        $this->colorService = $colorService;
        $this->serializer = new Serializer([new ObjectNormalizer()]);
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

    protected function teamRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Team::class);
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

    protected function favoritesRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Favorites::class);
    }

    protected function mediaRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Media::class);
    }

    protected function teamRolesRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(TeamRole::class);
    }

}