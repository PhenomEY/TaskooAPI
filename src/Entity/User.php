<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="integer")
     */
    private $role;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @ORM\OneToMany(targetEntity=Projects::class, mappedBy="mainUser", cascade={"persist"})
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity=Projects::class, mappedBy="ProjectUsers")
     */
    private $assignedProjects;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="assignedUser")
     */
    private $tasks;

    /**
     * @ORM\ManyToMany(targetEntity=Organisations::class, mappedBy="Users")
     */
    private $organisations;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="doneBy")
     */
    private $doneTasks;

    /**
     * @ORM\ManyToMany(targetEntity=Tasks::class, mappedBy="assignedUser")
     */
    private $assignedTasks;

    /**
     * @ORM\OneToMany(targetEntity=Notifications::class, mappedBy="user")
     */
    private $notifications;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->assignedProjects = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->organisations = new ArrayCollection();
        $this->doneTasks = new ArrayCollection();
        $this->assignedTasks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getRole(): ?int
    {
        return $this->role;
    }

    public function setRole(?int $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    public function setLastlogin(): void
    {
        $this->lastlogin = new \DateTime("now");
    }

    /**
     * @return Collection|Projects[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Projects $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setMainUser($this);
        }

        return $this;
    }

    public function removeProject(Projects $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getMainUser() === $this) {
                $project->setMainUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Projects[]
     */
    public function getAssignedProjects(): Collection
    {
        return $this->assignedProjects;
    }

    public function addAssignedProject(Projects $assignedProject): self
    {
        if (!$this->assignedProjects->contains($assignedProject)) {
            $this->assignedProjects[] = $assignedProject;
            $assignedProject->addProjectUser($this);
        }

        return $this;
    }

    public function removeAssignedProject(Projects $assignedProject): self
    {
        if ($this->assignedProjects->removeElement($assignedProject)) {
            $assignedProject->removeProjectUser($this);
        }

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
            $task->setAssignedUser($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getAssignedUser() === $this) {
                $task->setAssignedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Organisations[]
     */
    public function getOrganisations(): Collection
    {
        return $this->organisations;
    }

    public function addOrganisation(Organisations $organisation): self
    {
        if (!$this->organisations->contains($organisation)) {
            $this->organisations[] = $organisation;
            $organisation->addUser($this);
        }

        return $this;
    }

    public function removeOrganisation(Organisations $organisation): self
    {
        if ($this->organisations->removeElement($organisation)) {
            $organisation->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Tasks[]
     */
    public function getDoneTasks(): Collection
    {
        return $this->doneTasks;
    }

    public function addDoneTask(Tasks $doneTask): self
    {
        if (!$this->doneTasks->contains($doneTask)) {
            $this->doneTasks[] = $doneTask;
            $doneTask->setDoneBy($this);
        }

        return $this;
    }

    public function removeDoneTask(Tasks $doneTask): self
    {
        if ($this->doneTasks->removeElement($doneTask)) {
            // set the owning side to null (unless already changed)
            if ($doneTask->getDoneBy() === $this) {
                $doneTask->setDoneBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Tasks[]
     */
    public function getAssignedTasks(): Collection
    {
        return $this->assignedTasks;
    }

    public function addAssignedTask(Tasks $assignedTask): self
    {
        if (!$this->assignedTasks->contains($assignedTask)) {
            $this->assignedTasks[] = $assignedTask;
            $assignedTask->addAssignedUser($this);
        }

        return $this;
    }

    public function removeAssignedTask(Tasks $assignedTask): self
    {
        if ($this->assignedTasks->removeElement($assignedTask)) {
            $assignedTask->removeAssignedUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Notifications[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notifications $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
