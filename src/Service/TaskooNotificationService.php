<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Notifications;
use App\Entity\Projects;
use App\Entity\Tasks;
use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Persistence\ManagerRegistry;

class TaskooNotificationService
{
    public const TASK_ASSIGNED = 'task_assigned';
    public const PROJECT_ASSIGNED = 'project_assigned';

    private $doctrine;

    private $notificationRepository;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->notificationRepository = $this->doctrine->getRepository(Notifications::class);
    }

    public function load(User $user, int $limit, ?bool $visited = false) : array  {
        $criteria = new Criteria();
        $criteria->where(new Comparison('user', Comparison::EQ, $user));

        if($visited === null) {
            $criteria->andWhere(new Comparison('visited', Comparison::EQ, $visited));
        }

        $criteria->orderBy(['time' => Criteria::DESC]);
        $criteria->setMaxResults($limit);

        /** @var LazyCriteriaCollection $notifications */
        $notifications = $this->notificationRepository->matching($criteria);

        $data = [];

        /** @var Notifications $notification */
        foreach($notifications->getValues() as $key => $notification) {
            $data[$key] = [
                'time' => $notification->getTime(),
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'byUser' => $notification->getByUser()->getUserData()
            ];

            if($notification->getProject()) {
                $data[$key]['project'] = $notification->getProject()->getProjectMainData();
            }

            if($notification->getTask()) {
                $data[$key]['task'] = [
                    'id' => $notification->getTask()->getId(),
                    'name' => $notification->getTask()->getName()
                ];
            }
        }

        return $data;
    }

    public function create(User $user, User $byUser, ?Tasks $task, ?Projects $project, string $type) : bool
    {
        $notification = new Notifications();
        $notification->setMessage($type);
        $notification->setUser($user);
        $notification->setByUser($byUser);

        $this->checkIfNotificationAlreadyExists($user, $task, $project, $type);

        if($task) $notification->setTask($task);
        if($project) $notification->setProject($project);

        $manager = $this->doctrine->getManager();
        $manager->persist($notification);
        $manager->flush();

        return true;
    }

    private function checkIfNotificationAlreadyExists(User $user, ?Tasks $task, ?Projects $project, string $type) {
        $notifications = $this->notificationRepository->findBy([
            'user' => $user,
            'task' => $task,
            'message' => $type,
            'project' => $project
        ]);

        $manager = $this->doctrine->getManager();

        foreach ($notifications as $notification) {
            $manager->remove($notification);
        }

        $manager->flush();
    }

}