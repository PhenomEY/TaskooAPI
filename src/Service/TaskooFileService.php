<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\Tasks;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TaskooFileService
{
    private $targetDirectory;
    private $slugger;
    protected ManagerRegistry $doctrine;

    public function __construct($targetDirectory, SluggerInterface $slugger, ManagerRegistry $doctrine)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->doctrine = $doctrine;
    }

    public function upload(UploadedFile $file, Tasks $task = null)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $fileExtension = $file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);

            $media = new Media();
            $media->setFileName($fileName);
            $media->setExtension($fileExtension);
            $media->setFileSize($fileSize);
            $media->setMimeType($mimeType);

            if($task) {
                $media->setTask($task);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($media);
            $entityManager->flush();

        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return true;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}