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
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
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
     * @ORM\Column(type="datetime")
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

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->assignedProjects = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->organisations = new ArrayCollection();
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?Integer $role): self
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
}
