<?php declare(strict_types=1);

namespace Taskoo\Service;

use Taskoo\Entity\Settings;
use Taskoo\Entity\TempUrls;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class TaskooMailerService {

    private $mailer;

    private $doctrine;

    private $settings;

    private $serializer;

    private const SENDER = 'Taskoo <noreply@taskoo.de>';

    public function __construct(MailerInterface $mailer, ManagerRegistry $doctrine)
    {
        $this->mailer = $mailer;
        $this->doctrine = $doctrine;
        $this->serializer = new Serializer([new ObjectNormalizer()]);
        $this->settings = $this->serializer->normalize($this->doctrine->getRepository(Settings::class)->findAll()[0]);

    }

    public function sendInviteMail(TempUrls $inviteURL, int $hours): void {

        $user = $inviteURL->getUser();

        $email = (new TemplatedEmail())
            ->from(Address::create(static::SENDER))
            ->to(new Address($user->getEmail()))
            ->subject('Du wurdest zu Taskoo eingeladen!')
            ->htmlTemplate('emails/invite.html.twig')

            ->context([
                'expires_in' => $hours,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'invite_url' => $inviteURL->getHash(),
                'settings' => $this->settings
            ]);

        $this->mailer->send($email);
    }
}