<?php

namespace Taskoo\Entity;

use Taskoo\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=teamRepository::class)
 */
class Team
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
     * @ORM\OneToMany(targetEntity=Projects::class, mappedBy="team", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="organisations")
     */
    private $Users;

    /**
     * @ORM\ManyToOne(targetEntity=Color::class, inversedBy="organisations")
     */
    private $color;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->Users = new ArrayCollection();
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
            $project->setOrganisation($this);
        }

        return $this;
    }

    public function removeProject(Projects $project): self
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getTeam() === $this) {
                $project->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->Users;
    }

    public function addUser(User $user): self
    {
        if (!$this->Users->contains($user)) {
            $this->Users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->Users->removeElement($user);

        return $this;
    }

    public function getColor(): ?color
    {
        return $this->color;
    }

    public function setColor(?color $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getTeamData() : array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name
        ];

        if($this->getColor()) {
            $data['color'] = [
                'id' => $this->getColor()->getId(),
                'hexCode' => $this->getColor()->getHexCode()
            ];
        }

        return $data;
    }

    public function getTeamUsersData(): array
    {
        $userData = [];

        foreach($this->getUsers() as $user) {

            if(!$user->getActive()) continue;

            $userData[] = $user->getUserData();
        }

        return $userData;
    }
}
