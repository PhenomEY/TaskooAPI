<?php declare(strict_types=1);

namespace Taskoo\Service;

use Taskoo\Entity\Media;
use Taskoo\Entity\Tasks;
use Taskoo\Exception\NotAuthorizedException;
use Taskoo\Security\Authenticator;
use Taskoo\Struct\AuthStruct;
use Taskoo\Struct\SearchResultStruct;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Persistence\ManagerRegistry;

class SearchService {

    private $doctrine;

    private $taskRepository;

    private $mediaRepository;

    private $searchTerm;

    private $auth;

    private $authenticator;

    public function __construct(ManagerRegistry $doctrine, Authenticator $authenticator)
    {
        $this->doctrine = $doctrine;
        $this->authenticator = $authenticator;
        $this->taskRepository = $this->doctrine->getRepository(Tasks::class);
        $this->mediaRepository = $this->doctrine->getRepository(Media::class);
    }

    public function search(string $searchTerm, AuthStruct $auth, ?int $limit = 20, ?int $offset  = 0, $type = 'all') : SearchResultStruct
    {
        $this->searchTerm = $searchTerm;
        $this->auth = $auth;

        $taskSearchCriteria = new Criteria();
        $taskSearchCriteria->where(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new Comparison('name', Comparison::CONTAINS, $searchTerm),
            new Comparison('description', Comparison::CONTAINS, $searchTerm)
        ]));

        $taskSearchCriteria->setMaxResults($limit);
        $taskSearchCriteria->setFirstResult($offset);

        $mediaSearchCriteria = new Criteria();
        $mediaSearchCriteria->where(new CompositeExpression(CompositeExpression::TYPE_AND, [
            new Comparison('fileName', Comparison::CONTAINS, $searchTerm),
            new Comparison('task', Comparison::NEQ, null)
        ]));

        $mediaSearchCriteria->setMaxResults($limit);
        $mediaSearchCriteria->setFirstResult($offset);

        return $this->handleSearchRequest($taskSearchCriteria, $mediaSearchCriteria, $type);
    }

    private function handleSearchRequest(Criteria $taskSearchCriteria, Criteria $mediaSearchCriteria, $type) : SearchResultStruct
    {
        $searchResults = new SearchResultStruct();

        switch ($type) {
            case 'media':
                /** @var LazyCriteriaCollection $foundMedias */
                $foundMedias = $this->mediaRepository->matching($mediaSearchCriteria);
                $searchResults->setMediaResultCount($foundMedias->count());
                $searchResults->setMedias($this->handleMediaResults($foundMedias));
                break;
            case 'task':
                /** @var LazyCriteriaCollection $foundTasks */
                $foundTasks = $this->taskRepository->matching($taskSearchCriteria);
                $searchResults->setTaskResultCount($foundTasks->count());
                $searchResults->setTasks($this->handleTaskResults($foundTasks));
                break;
            default:
                /** @var LazyCriteriaCollection $foundMedias */
                $foundMedias = $this->mediaRepository->matching($mediaSearchCriteria);
                /** @var LazyCriteriaCollection $foundTasks */
                $foundTasks = $this->taskRepository->matching($taskSearchCriteria);
                $searchResults->setMediaResultCount($foundMedias->count());
                $searchResults->setMedias($this->handleMediaResults($foundMedias));
                $searchResults->setTaskResultCount($foundTasks->count());
                $searchResults->setTasks($this->handleTaskResults($foundTasks));
        }

        return $searchResults;
    }

    private function handleMediaResults(LazyCriteriaCollection $medias) : array
    {
        $data = [];

        /** @var Media $media */
        foreach($medias->getValues() as $media) {
            try {
                $this->authenticator->checkProjectPermission($this->auth, $media->getTask()->getTaskGroup()->getProject()->getId());
            } catch (NotAuthorizedException $e) {
                continue;
            }

            $data[] = [
                'id' => $media->getId(),
                'filePath' => $media->getFilePath(),
                'fileName' => $media->getFileName(),
                'fileSize' => $media->getFileSize(),
                'uploadedAt' => $media->getUploadedAt(),
                'task'=> [
                    'id' =>  $media->getTask()->getId(),
                    'name' => $media->getTask()->getName(),
                    'project' => $media->getTask()->getTaskGroup()->getProject()->getProjectMainData()
                ]
            ];
        }

        return $data;
    }

    private function handleTaskResults(LazyCriteriaCollection $tasks) : array
    {
        $data = [];

        /** @var Tasks $task */
        foreach ($tasks->getValues() as $task) {
            try {
                $this->authenticator->checkProjectPermission($this->auth, $task->getTaskGroup()->getProject()->getId());
            } catch (NotAuthorizedException $e) {
                continue;
            }

            $data[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $this->truncateDescription($task->getDescription()),
                'project' => $task->getTaskGroup()->getProject()->getProjectMainData()
            ];
        }

        return $data;
    }

    private function truncateDescription(?string $description) : ?string
    {
        if(!$description) return null;

        $lowerSearchTerm = strtolower($this->searchTerm);
        $termPosition = strpos(strtolower($description), $lowerSearchTerm);
        $termLength = strlen($this->searchTerm);

        $start = $termPosition > 20 ? ($termPosition - 20) : 0;
        $end = $termLength + 40;

        $newDescription = substr($description, $start, $end);

        $newDescription = $start > 0 ? '...'.$newDescription : $newDescription;
        $newDescription = strlen($description) > ($start + $termLength + 40) ? $newDescription.'...' : $newDescription;

        //remove HTML
        $newDescription = preg_replace('#<[^>]+>#', ' ', $newDescription);

        return $newDescription;
    }


}