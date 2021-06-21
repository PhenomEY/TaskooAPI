<?php

namespace Taskoo\Service;

use Gumlet\ImageResize;
use Taskoo\Entity\Media;
use Taskoo\Entity\Tasks;
use Taskoo\Entity\User;
use Taskoo\Exception\InvalidFileTypeException;
use Taskoo\Exception\InvalidRequestException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileService
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
        $altFileName = $safeFilename.'.'.$file->guessExtension();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $fileExtension = $file->guessExtension();

        $allowedTypes = self::ALLOWED_IMAGES;

        try {
            $fileDirectory = 'avatars/'.$user->getId();
            $fileName = 'avatar-'.uniqid().'.'.$file->guessExtension();

            if($task) {
                $fileDirectory = $task->getId();
                $allowedTypes = array_merge(self::ALLOWED_IMAGES, self::ALLOWED_FILES);
                $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            }

            if(!in_array($fileExtension, $allowedTypes)) throw new InvalidFileTypeException();

            if(!$task) {
                //resize avatar image if too large
                $this->resizeImage($file->getRealPath());
            }

            $file->move($this->getTargetDirectory().'/'.$fileDirectory, $fileName);

            $media = new Media();
            $media->setFileName($altFileName);
            $media->setExtension($fileExtension);
            $media->setFileSize($fileSize);
            $media->setMimeType($mimeType);
            $media->setUploadedBy($user);
            $media->setFilePath($fileDirectory.'/'.$fileName);

            $entityManager = $this->doctrine->getManager();

            if($task) {
                $media->setTask($task);
            } else {
                if($user->getAvatar()) $this->delete($user->getAvatar());

                $user->setAvatar($media);
                $entityManager->persist($user);
            }

            $entityManager->persist($media);
            $entityManager->flush();

            return $media;

        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }

        return null;
    }

    public function delete(Media $media)
    {
        $mediaPath = $this->getTargetDirectory();
        $filePath = $media->getFilePath();

        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($media);
        $entityManager->flush();

        $fileSystem = new Filesystem();
        $fileSystem->remove($mediaPath.'/'.$filePath);
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    private function resizeImage(string $filePath)
    {
        $resize = new ImageResize($filePath);
        $height = $resize->getDestHeight();
        $width = $resize->getDestWidth();

        if($height > 150 || $width > 150) {
            $resize->resizeToBestFit(150, 150);
            $resize->save($filePath);
        }
    }
}