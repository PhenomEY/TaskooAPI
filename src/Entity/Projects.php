<?php

namespace App\Entity;

use App\Repository\ProjectsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @ORM\Entity(repositoryClass=ProjectsRepository::class)
 */
class Projects
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
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="projects")
     */
    private $mainUser;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deadline;

    /**
     * @ORM\OneToMany(targetEntity=TaskGroups::class, mappedBy="project", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @OrderBy({"position" = "ASC"})
     */
    private $taskGroups;

    /**
     * @ORM\ManyToOne(targetEntity=Team::class, inversedBy="projects")
     */
    private $team;

    /**
     * @ORM\Column(type="boolean")
     */
    private $closed = false;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="assignedProjects")
     */
    private $ProjectUsers;

    /**
     * @ORM\Column(type="text", length=555, nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->taskGroups = new ArrayCollection();
        $this->ProjectUsers = new ArrayCollection();
        $this->createdAt = new \DateTime("now");
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

    public function getMainUser(): ?User
    {
        return $this->mainUser;
    }

    public function setMainUser(?User $mainUser): self
    {
        $this->mainUser = $mainUser;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): self
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * @return Collection|TaskGroups[]
     */
    public function getTaskGroups(): Collection
    {
        return $this->taskGroups;
    }

    public function addTaskGroup(TaskGroups $taskGroup): self
    {
        if (!$this->taskGroups->contains($taskGroup)) {
            $this->taskGroups[] = $taskGroup;
            $taskGroup->setProject($this);
        }

        return $this;
    }

    public function removeTaskGroup(TaskGroups $taskGroup): self
    {
        if ($this->taskGroups->removeElement($taskGroup)) {
            // set the owning side to null (unless already changed)
            if ($taskGroup->getProject() === $this) {
                $taskGroup->setProject(null);
            }
        }

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getProjectUsers(): Collection
    {
        return $this->ProjectUsers;
    }

    public function addProjectUser(User $projectUser): self
    {
        if (!$this->ProjectUsers->contains($projectUser)) {
            $this->ProjectUsers[] = $projectUser;
        }

        return $this;
    }

    public function removeProjectUser(User $projectUser): self
    {
        $this->ProjectUsers->removeElement($projectUser);

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

    /**
     * returns array of main project data
     */
    public function getProjectMainData(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'deadline' => $this->deadline,
            'isClosed' => $this->closed,
            'description' => $this->description
        ];

        if($this->getTeam()) {
            $data['team'] = $this->getTeam()->getTeamData();
        }

        return $data;
    }

    public function getProjectUsersData(): array
    {
        $userData = [];

        foreach($this->getProjectUsers() as $user) {

            if(!$user->getActive()) continue;

            $userData[] = $user->getUserData();
        }

        return $userData;
    }

    public function getMainUserData(): ?array
    {
        if(!$this->mainUser) return null;

        return $this->mainUser->getUserData();
    }
}
