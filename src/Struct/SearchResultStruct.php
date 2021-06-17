<?php declare(strict_types=1);

namespace Taskoo\Struct;

use Doctrine\ORM\LazyCriteriaCollection;

class SearchResultStruct {

    protected array $tasks;

    protected array $medias;

    protected int $taskResultCount;

    protected int $mediaResultCount;

    /**
     * @return int
     */
    public function getTaskResultCount(): int
    {
        return $this->taskResultCount;
    }

    /**
     * @param int $taskResultCount
     */
    public function setTaskResultCount(int $taskResultCount): void
    {
        $this->taskResultCount = $taskResultCount;
    }

    /**
     * @return int
     */
    public function getMediaResultCount(): int
    {
        return $this->mediaResultCount;
    }

    /**
     * @param int $mediaResultCount
     */
    public function setMediaResultCount(int $mediaResultCount): void
    {
        $this->mediaResultCount = $mediaResultCount;
    }

    /**
     * @return array
     */
    public function getMedias(): array
    {
        return $this->medias;
    }

    /**
     * @param array $medias
     */
    public function setMedias(array $medias): void
    {
        $this->medias = $medias;
    }

    /**
     * @return array
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @param array $tasks
     */
    public function setTasks(array $tasks): void
    {
        $this->tasks = $tasks;
    }

}