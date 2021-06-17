<?php declare(strict_types=1);

namespace Taskoo\Service;

use Taskoo\Entity\Media;
use Taskoo\Entity\TaskGroups;
use Taskoo\Entity\Tasks;
use Taskoo\Entity\User;
use Composer\Autoload\ClassLoader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Persistence\ManagerRegistry;

class TaskGroupService
{

    private $doctrine;

    private $taskRepository;

    private $mediaRepository;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->taskRepository = $this->doctrine->getRepository(Tasks::class);
        $this->mediaRepository = $this->doctrine->getRepository(Media::class);
    }

    public function loadTasks(TaskGroups $taskGroup, ?bool $done = null) : array {
        $taskCriteria = new Criteria();
        $taskCriteria->where(new CompositeExpression(CompositeExpression::TYPE_AND, [
            new Comparison('TaskGroup',Comparison::EQ, $taskGroup),
            new Comparison('mainTask', Comparison::IS, NULL)
        ]));

        if($done === true || $done === false) {
            $taskCriteria->andWhere(new Comparison('done', Comparison::EQ, $done));
        }

        $taskCriteria->orderBy(['position' => Criteria::ASC]);

        /** @var LazyCriteriaCollection $tasks */
        $tasks = $this->taskRepository->matching($taskCriteria);

        $data = [];

        /** @var Tasks $task */
        foreach ($tasks->getValues() as $key => $task) {
            $data[$key] = [
                'dateDue' => $task->getDateDue(),
                'id' => $task->getId(),
                'isDone' => $task->getDone(),
                'name' => $task->getName(),
                'doneAt' => $task->getDoneAt(),
                'higherPriority' => $task->getHigherPriority()
            ];

            if($task->getDescription()) {
                $data[$key]['description'] = true;
            }

            if($task->getSubTasks()->count() > 0) {
                $data[$key]['subTasks'] = true;
            }

            if($task->getAssignedUser()->count() > 0) {
                /** @var User $user */
                $user = $task->getAssignedUser()->first();
                $data[$key]['user'] = $user->getUserData();
            }

            if($this->mediaRepository->findOneBy(['task' => $task->getId()])) {
                $data[$key]['hasFiles'] = true;
            }
        }

        return $data;
    }
}