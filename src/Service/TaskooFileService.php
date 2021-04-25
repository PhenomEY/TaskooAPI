<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\Tasks;
use App\Entity\User;
use App\Exception\InvalidFileTypeException;
use App\Exception\InvalidRequestException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TaskooFileService
{
    private String $targetDirectory;

    private SluggerInterface $slugger;

    protected ManagerRegistry $doctrine;

    public const DEFAULT_FILE = 'DEFAULT';

    public const IS_AVATAR = 'IS_AVATAR';

    public const ALLOWED_IMAGES = [
        'jpg',
        'jpeg',
        'png',
        'gif'
    ];

    public const ALLOWED_FILES = [
        'zip',
        'psd',
        'rar',
        'pdf',
        'svg'
    ];

    public function __construct($targetDirectory, SluggerInterface $slugger, ManagerRegistry $doctrine)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->doctrine = $doctrine;
    }

    public function upload(UploadedFile $file, User $user, Tasks $task = null) : ?Media
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $fileExtension = $file->guessExtension();

        $allowedTypes = self::ALLOWED_IMAGES;

        try {
            $fileDirectory = 'avatars';

            if($task) {
                $fileDirectory = $task->getId();
                $allowedTypes = array_merge(self::ALLOWED_IMAGES, self::ALLOWED_FILES);
            }

            if(!in_array($fileExtension, $allowedTypes)) throw new InvalidFileTypeException();

            $file->move($this->getTargetDirectory().'/'.$fileDirectory, $fileName);

            $media = new Media();
            $media->setFileName($fileName);
            $media->setExtension($fileExtension);
            $media->setFileSize($fileSize);
            $media->setMimeType($mimeType);
            $media->setUploadedBy($user);
            $media->setUploadedAt(new \DateTime('now'));
            $media->setFilePath($fileDirectory.'/'.$fileName);

            if($task) {
                $media->setTask($task);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($media);
            $entityManager->flush();

            return $media;

        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return null;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}