<?php

namespace Taskoo\Entity;

use Taskoo\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SettingsRepository::class)
 */
class Settings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=60)
     */
    private $appUrl;

    /**
     * @ORM\Column(type="string", length=60)
     */
    private $mailSender;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppUrl(): ?string
    {
        return $this->appUrl;
    }

    public function setAppUrl(string $appUrl): self
    {
        $this->appUrl = $appUrl;

        return $this;
    }

    public function getMailSender(): ?string
    {
        return $this->mailSender;
    }

    public function setMailSender(string $mailSender): self
    {
        $this->mailSender = $mailSender;

        return $this;
    }
}
