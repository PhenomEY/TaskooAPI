<?php declare(strict_types=1);

namespace App\Api;

use App\Entity\Color;
use App\Entity\Favorites;
use App\Entity\Media;
use App\Entity\Notifications;
use App\Entity\Organisations;
use App\Entity\Projects;
use App\Entity\Settings;
use App\Entity\TaskGroups;
use App\Entity\Tasks;
use App\Entity\User;
use App\Security\TaskooAuthenticator;
use App\Service\TaskooColorService;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class TaskooApiController extends AbstractController
{
    protected $authenticator;

    protected $responseManager;

    protected $colorService;

    protected $serializer;

    public function __construct(TaskooAuthenticator $authenticator, TaskooResponseManager $responseManager, TaskooColorService $colorService)
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

    protected function favoritesRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Favorites::class);
    }

    protected function mediaRepository(): ObjectRepository {
        return $this->getDoctrine()->getRepository(Media::class);
    }

}