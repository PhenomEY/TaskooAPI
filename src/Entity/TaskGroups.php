<?php

namespace App\Entity;

use App\Repository\TaskGroupsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @ORM\Entity(repositoryClass=TaskGroupsRepository::class)
 */
class TaskGroups
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Projects::class, inversedBy="taskGroups", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="TaskGroup", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @OrderBy({"position" = "ASC"})
     */
    private $tasks;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection|Tasks[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Tasks $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setTaskGroup($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getTaskGroup() === $this) {
                $task->setTaskGroup(null);
            }
        }

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
