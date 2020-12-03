<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TasksRepository::class)
 */
class Tasks
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=TaskGroups::class, inversedBy="tasks", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $TaskGroup;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateDue;

    /**
     * @ORM\Column(type="boolean")
     */
    private $done;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="doneTasks")
     */
    private $doneBy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $doneAt;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="assignedTasks")
     */
    private $assignedUser;

    /**
     * @ORM\ManyToOne(targetEntity=Tasks::class, inversedBy="subTasks", cascade={"remove"})
     */
    private $mainTask;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="mainTask")
     */
    private $subTasks;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $createdBy;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->assignedUser = new ArrayCollection();
        $this->subTasks = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTaskGroup(): ?TaskGroups
    {
        return $this->TaskGroup;
    }

    public function setTaskGroup(?TaskGroups $TaskGroup): self
    {
        $this->TaskGroup = $TaskGroup;

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

    public function getDateDue(): ?\DateTimeInterface
    {
        return $this->dateDue;
    }

    public function setDateDue(?\DateTimeInterface $dateDue): self
    {
        $this->dateDue = $dateDue;

        return $this;
    }

    public function getDone(): ?bool
    {
        return $this->done;
    }

    public function setDone(bool $done): self
    {
        $this->done = $done;

        return $this;
    }

    public function getDoneBy(): ?User
    {
        return $this->doneBy;
    }

    public function setDoneBy(?User $doneBy): self
    {
        $this->doneBy = $doneBy;

        return $this;
    }

    public function getDoneAt(): ?\DateTimeInterface
    {
        return $this->doneAt;
    }

    public function setDoneAt(?\DateTimeInterface $doneAt): self
    {
        $this->doneAt = $doneAt;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAssignedUser(): Collection
    {
        return $this->assignedUser;
    }

    public function addAssignedUser(User $assignedUser): self
    {
        if (!$this->assignedUser->contains($assignedUser)) {
            $this->assignedUser[] = $assignedUser;
        }

        return $this;
    }

    public function removeAssignedUser(User $assignedUser): self
    {
        $this->assignedUser->removeElement($assignedUser);

        return $this;
    }

    public function getMainTask(): ?self
    {
        return $this->mainTask;
    }

    public function setMainTask(?self $mainTask): self
    {
        $this->mainTask = $mainTask;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getSubTasks(): Collection
    {
        return $this->subTasks;
    }

    public function addSubTask(self $subTask): self
    {
        if (!$this->subTasks->contains($subTask)) {
            $this->subTasks[] = $subTask;
            $subTask->setMainTask($this);
        }

        return $this;
    }

    public function removeSubTask(self $subTask): self
    {
        if ($this->subTasks->removeElement($subTask)) {
            // set the owning side to null (unless already changed)
            if ($subTask->getMainTask() === $this) {
                $subTask->setMainTask(null);
            }
        }

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

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
}
