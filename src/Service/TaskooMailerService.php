<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;


class TaskooMailerService {

    private const SENDER = 'Taskoo <noreply@taskoo.de>';

    public function sendMail(MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from(Address::create(static::SENDER))
            ->to(new Address('damian95@gmx.de'))
            ->subject('Thanks for signing up!')

            // path of the Twig template to render
            ->htmlTemplate('emails/invite.html.twig');

            // pass variables (name => value) to the template
//            ->context([
//                'expiration_date' => new \DateTime('+7 days'),
//                'username' => 'foo',
//            ]);

        $mailer->send($email);
    }

}