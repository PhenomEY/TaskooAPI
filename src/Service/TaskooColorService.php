<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Color;
use Doctrine\Persistence\ManagerRegistry;

class TaskooColorService {

    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getRandomColor(): object {
        //get random color
        $allColors = $this->doctrine->getRepository(Color::class)->findAll();
        $colorId = rand ( 1, count($allColors));
        $organisationColor = $this->doctrine->getRepository(Color::class)->find($colorId);

        return $organisationColor;
    }
}