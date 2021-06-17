<?php

namespace Taskoo\Entity;

use Taskoo\Repository\NotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=NotificationsRepository::class)
 */
class Notifications
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $byUser;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notifications", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Projects::class, cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity=Tasks::class, cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $task;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     */
    private $time;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $visited;

    public function __construct()
    {
        $this->time = new \DateTime('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getByUser(): ?User
    {
        return $this->byUser;
    }

    public function setByUser(?User $byUser): self
    {
        $this->byUser = $byUser;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): ?Projects
    {
        return $this->project;
    }

    public function setProject(?Projects $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTask(): ?Tasks
    {
        return $this->task;
    }

    public function setTask(?Tasks $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function getVisited(): ?bool
    {
        return $this->visited;
    }

    public function setVisited(?bool $visited): self
    {
        $this->visited = $visited;

        return $this;
    }
}
