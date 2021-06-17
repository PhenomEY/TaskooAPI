<?php declare(strict_types=1);
namespace Taskoo\Struct;

use Taskoo\Entity\User;

class AuthStruct {

    protected string $type;

    protected ?User $user;


    public function __construct(string $type, ?User $user = null)
    {
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}