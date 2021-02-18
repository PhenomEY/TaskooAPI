<?php

namespace App\Service;

use App\Entity\TempUrls;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;


class TaskooMailerService {

    private $mailer;

    private const SENDER = 'Taskoo <noreply@taskoo.de>';

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendInviteMail(TempUrls $inviteURL, int $hours) {

        $user = $inviteURL->getUser();

        $email = (new TemplatedEmail())
            ->from(Address::create(static::SENDER))
            ->to(new Address($user->getEmail()))
            ->subject('Du wurdest zu Taskoo eingeladen!')

            // path of the Twig template to render
            ->htmlTemplate('emails/invite.html.twig')

            ->context([
                'expires_in' => $hours,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'invite_url' => $inviteURL->getHash()
            ]);

        $this->mailer->send($email);
    }

}