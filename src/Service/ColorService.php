<?php declare(strict_types=1);

namespace Taskoo\Service;

use Taskoo\Entity\Color;
use Doctrine\Persistence\ManagerRegistry;

class ColorService {

    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getRandomColor(): object {
        //get random color
        $allColors = $this->doctrine->getRepository(Color::class)->findAll();
        $colorKey = rand ( 0, (count($allColors) - 1));

        return $allColors[$colorKey];
    }
}