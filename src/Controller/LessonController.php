<?php

namespace App\Controller;

use App\Document\Course;
use App\Document\Lesson;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class LessonController extends AbstractController
{
    public function __construct(
        private DocumentManager $documentManager,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/lessons', name: 'create_lesson', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $lesson = new Lesson();

            // Récupérer les données du formulaire
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $courseId = $request->request->get('course');
            $file = $request->files->get('file');

            // Validation des champs obligatoires
            if (!$title) {
                return $this->json(['error' => 'Le titre est obligatoire'], Response::HTTP_BAD_REQUEST);
            }

            $lesson->setTitle($title);

            if ($content) {
                $lesson->setContent($content);
            }

            // Gestion du cours
            if ($courseId) {
                // Nettoyer l'ID si c'est un IRI
                if (str_starts_with($courseId, '/api/courses/')) {
                    $courseId = str_replace('/api/courses/', '', $courseId);
                }

                $course = $this->documentManager->getRepository(Course::class)->find($courseId);
                if ($course) {
                    $lesson->setCourse($course);
                } else {
                    return $this->json(['error' => 'Cours introuvable'], Response::HTTP_BAD_REQUEST);
                }
            }

            // Gestion du fichier
            if ($file) {
                $fileName = $this->handleFileUpload($file);
                if (!$fileName) {
                    return $this->json(['error' => 'Erreur lors de l\'upload du fichier'], Response::HTTP_BAD_REQUEST);
                }
                $lesson->setFilePath($fileName);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($lesson);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder
            $this->documentManager->persist($lesson);
            $this->documentManager->flush();

            return $this->json([
                '@context' => '/api/contexts/Lesson',
                '@id' => '/api/lessons/' . $lesson->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $lesson->getId(),
                'title' => $lesson->getTitle(),
                'content' => $lesson->getContent(),
                'filePath' => $lesson->getFilePath(),
                'course' => $lesson->getCourse() ? '/api/courses/' . $lesson->getCourse()->getId() : null,
                'createdAt' => $lesson->getCreatedAt()?->format('c'),
                'updatedAt' => $lesson->getUpdatedAt()?->format('c'),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/lessons/{id}', name: 'update_lesson', methods: ['POST'])] // POST car multipart
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $lesson = $this->documentManager->getRepository(Lesson::class)->find($id);

            if (!$lesson) {
                return $this->json(['error' => 'Leçon introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Récupérer les données du formulaire
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $courseId = $request->request->get('course');
            $file = $request->files->get('file');

            // Mise à jour des champs
            if ($title !== null) {
                $lesson->setTitle($title);
            }

            if ($content !== null) {
                $lesson->setContent($content);
            }

            // Gestion du cours
            if ($courseId !== null) {
                if ($courseId === '') {
                    $lesson->setCourse(null);
                } else {
                    // Nettoyer l'ID si c'est un IRI
                    if (str_starts_with($courseId, '/api/courses/')) {
                        $courseId = str_replace('/api/courses/', '', $courseId);
                    }

                    $course = $this->documentManager->getRepository(Course::class)->find($courseId);
                    if ($course) {
                        $lesson->setCourse($course);
                    } else {
                        return $this->json(['error' => 'Cours introuvable'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Gestion du fichier
            if ($file) {
                // Supprimer l'ancien fichier si il existe
                if ($lesson->getFilePath()) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/lessons/' . $lesson->getFilePath();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $fileName = $this->handleFileUpload($file);
                if (!$fileName) {
                    return $this->json(['error' => 'Erreur lors de l\'upload du fichier'], Response::HTTP_BAD_REQUEST);
                }
                $lesson->setFilePath($fileName);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($lesson);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder
            $this->documentManager->flush();

            return $this->json([
                '@context' => '/api/contexts/Lesson',
                '@id' => '/api/lessons/' . $lesson->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $lesson->getId(),
                'title' => $lesson->getTitle(),
                'content' => $lesson->getContent(),
                'filePath' => $lesson->getFilePath(),
                'course' => $lesson->getCourse() ? '/api/courses/' . $lesson->getCourse()->getId() : null,
                'createdAt' => $lesson->getCreatedAt()?->format('c'),
                'updatedAt' => $lesson->getUpdatedAt()?->format('c'),
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/lessons/{id}/file', name: 'delete_lesson_file', methods: ['DELETE'])]
    public function deleteFile(string $id): JsonResponse
    {
        try {
            $lesson = $this->documentManager->getRepository(Lesson::class)->find($id);

            if (!$lesson) {
                return $this->json(['error' => 'Leçon introuvable'], Response::HTTP_NOT_FOUND);
            }

            if ($lesson->getFilePath()) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/lessons/' . $lesson->getFilePath();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $lesson->setFilePath(null);
                $this->documentManager->flush();
            }

            return $this->json(['message' => 'Fichier supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleFileUpload($file): ?string
    {
        // Vérifications de sécurité
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return null;
        }

        // Limite de taille (10MB)
        if ($file->getSize() > 10485760) {
            return null;
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/lessons',
                $fileName
            );
            return $fileName;
        } catch (FileException $e) {
            return null;
        }
    }
}
