<?php

namespace App\Entity;

use App\Repository\UserRightsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRightsRepository::class)
 */
class UserRights
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $administration;

    /**
     * @ORM\Column(type="boolean")
     */
    private $projectCreate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $projectEdit;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="userRights", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdministration(): ?bool
    {
        return $this->administration;
    }

    public function setAdministration(bool $administration): self
    {
        $this->administration = $administration;

        return $this;
    }

    public function getProjectCreate(): ?bool
    {
        return $this->projectCreate;
    }

    public function setProjectCreate(bool $projectCreate): self
    {
        $this->projectCreate = $projectCreate;

        return $this;
    }

    public function getProjectEdit(): ?bool
    {
        return $this->projectEdit;
    }

    public function setProjectEdit(bool $projectEdit): self
    {
        $this->projectEdit = $projectEdit;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setDefaults()
    {
        $this->administration = false;
        $this->projectCreate = false;
        $this->projectEdit = false;

        return $this;
    }
}
