<?php declare(strict_types=1);

namespace App\Api;

use App\Controller\Auth;
use App\Controller\Invite;
use App\Controller\Files;
use App\Security\TaskooAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriber implements EventSubscriberInterface
{
    private $authenticator;

    public function __construct(TaskooAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if(!$controller instanceof Auth && !$controller instanceof Files && !$controller instanceof Invite) {
            $token = $event->getRequest()->headers->get('authorization');
            $auth = $this->authenticator->verifyToken($token);
            $event->getRequest()->attributes->set('auth', $auth);
        }
    }
}