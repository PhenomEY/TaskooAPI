<?php

namespace Taskoo\Entity;

use Taskoo\Repository\UserRepository;
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
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastlogin;

    /**
     * @ORM\OneToMany(targetEntity=Projects::class, mappedBy="mainUser", cascade={"persist"})
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity=Projects::class, mappedBy="ProjectUsers", cascade={"persist"})
     */
    private $assignedProjects;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="assignedUser", cascade={"persist"})
     */
    private $tasks;

    /**
     * @ORM\ManyToMany(targetEntity=Team::class, mappedBy="Users", cascade={"persist"})
     */
    private $teams;

    /**
     * @ORM\OneToMany(targetEntity=Tasks::class, mappedBy="doneBy", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $doneTasks;

    /**
     * @ORM\ManyToMany(targetEntity=Tasks::class, mappedBy="assignedUser", cascade={"persist"})
     */
    private $assignedTasks;

    /**
     * @ORM\OneToMany(targetEntity=Notifications::class, mappedBy="user", orphanRemoval=true)
     */
    private $notifications;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\OneToOne(targetEntity=UserPermissions::class, mappedBy="user", cascade={"remove"})
     */
    private $userPermissions;

    /**
     * @ORM\ManyToOne(targetEntity=Color::class, cascade={"persist"})
     */
    private $color;

    /**
     * @ORM\OneToMany(targetEntity=Favorites::class, mappedBy="user", orphanRemoval=true)
     */
    private $favorites;

    /**
     * @ORM\OneToOne(targetEntity=Media::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $avatar;

    /**
     * @ORM\ManyToOne(targetEntity=TeamRole::class, inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $teamRole;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->assignedProjects = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->doneTasks = new ArrayCollection();
        $this->assignedTasks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->favorites = new ArrayCollection();
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
     * @return Collection|Team[]
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams[] = $team;
            $team->addUser($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): self
    {
        if ($this->teams->removeElement($team)) {
            $team->removeUser($this);
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

    public function getUserPermissions(): ?UserPermissions
    {
        return $this->userPermissions;
    }

    public function setUserPermissions(UserPermissions $userPermissions): self
    {
        // set the owning side of the relation if necessary
        if ($userPermissions->getUser() !== $this) {
            $userPermissions->setUser($this);
        }

        $this->userPermissions = $userPermissions;

        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection|Favorites[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorites $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorites $favorite): self
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }

    public function getAvatar(): ?Media
    {
        return $this->avatar;
    }

    public function setAvatar(?Media $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getUserData() : array
    {
        $userData = [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email
        ];

        if($this->getAvatar()) {
            $userData['avatar'] = [
                'filePath' => $this->getAvatar()->getFilePath()
            ];
        }

        if($this->getColor()) {
            $userData['color'] = [
                'id' => $this->getColor()->getId(),
                'hexCode' => $this->getColor()->getHexCode()
            ];
        }

        if($this->getTeamRole()) {
            $userData['teamrole'] = [
                'name' => $this->getTeamRole()->getName(),
                'id' => $this->getTeamRole()->getId(),
                'priority' => $this->getTeamRole()->getPriority()
            ];
        }

        return $userData;
    }

    public function getTeamRole(): ?TeamRole
    {
        return $this->teamRole;
    }

    public function setTeamRole(?TeamRole $teamRole): self
    {
        $this->teamRole = $teamRole;

        return $this;
    }
}
