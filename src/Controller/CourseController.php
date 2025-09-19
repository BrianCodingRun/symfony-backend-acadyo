<?php

namespace App\Controller;

use App\Document\Classroom;
use App\Document\Course;
use App\Service\CloudinaryService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class CourseController extends AbstractController
{
    public function __construct(
        private DocumentManager $documentManager,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator,
        private CloudinaryService $cloudinaryService
    ) {
    }

    #[Route('/courses', name: 'create_course', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $course = new Course();

            // Récupérer les données du formulaire
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $classroomId = $request->request->get('classroom');
            $file = $request->files->get('file');

            if (!$title) {
                return $this->json(['error' => 'Le titre est obligatoire'], Response::HTTP_BAD_REQUEST);
            }

            $course->setTitle($title);

            if ($content) {
                $course->setContent($content);
            }

            // Associer au classroom
            if ($classroomId) {
                if (str_starts_with($classroomId, '/api/classrooms/')) {
                    $classroomId = str_replace('/api/classrooms/', '', $classroomId);
                }

                $classroom = $this->documentManager->getRepository(Classroom::class)->find($classroomId);
                if ($classroom) {
                    $course->setClassroom($classroom);
                } else {
                    return $this->json(['error' => 'Classroom introuvable'], Response::HTTP_BAD_REQUEST);
                }
            }

            // Upload fichier Cloudinary
            if ($file) {
                $result = $this->cloudinaryService->uploadFile($file->getPathname(), [
                    'folder' => 'acadyo/courses',
                    'resource_type' => 'auto',
                ]);

                if (!$result || !isset($result['secure_url'])) {
                    return $this->json(['error' => 'Erreur lors de l\'upload sur Cloudinary'], Response::HTTP_BAD_REQUEST);
                }

                $course->setFilePath($result['secure_url']);
                $course->setFilePublicId($result['public_id']); // nouveau champ
            }

            // Validation
            $errors = $this->validator->validate($course);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->documentManager->persist($course);
            $this->documentManager->flush();

            return $this->json([
                '@id' => '/api/courses/' . $course->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'content' => $course->getContent(),
                'filePath' => $course->getFilePath(),
                'classroom' => $course->getClassroom() ? '/api/classrooms/' . $course->getClassroom()->getId() : null,
                'createdAt' => $course->getCreatedAt()?->format('c'),
                'updatedAt' => $course->getUpdatedAt()?->format('c'),
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/courses/{id}', name: 'update_course', methods: ['POST'])] // POST car multipart
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $course = $this->documentManager->getRepository(Course::class)->find($id);

            if (!$course) {
                return $this->json(['error' => 'Leçon introuvable'], Response::HTTP_NOT_FOUND);
            }

            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $classroomId = $request->request->get('classroom');
            $file = $request->files->get('file');

            if ($title !== null) {
                $course->setTitle($title);
            }

            if ($content !== null) {
                $course->setContent($content);
            }

            // Associer au cours
            if ($classroomId !== null) {
                if ($classroomId === '') {
                    $course->setClassroom(null);
                } else {
                    if (str_starts_with($classroomId, '/api/classrooms/')) {
                        $classroomId = str_replace('/api/classrooms/', '', $classroomId);
                    }

                    $classroom = $this->documentManager->getRepository(Classroom::class)->find($classroomId);
                    if ($classroom) {
                        $course->setClassroom($classroom);
                    } else {
                        return $this->json(['error' => 'Cours introuvable'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Upload fichier Cloudinary
            if ($file) {
                // Supprimer ancien fichier Cloudinary
                if ($course->getFilePublicId()) {
                    $this->cloudinaryService->deleteFile($course->getFilePublicId());
                }

                $result = $this->cloudinaryService->uploadFile($file->getPathname(), [
                    'folder' => 'acadyo/courses',
                    'resource_type' => 'auto',
                ]);

                if (!$result || !isset($result['secure_url'])) {
                    return $this->json(['error' => 'Erreur lors de l\'upload sur Cloudinary'], Response::HTTP_BAD_REQUEST);
                }

                $course->setFilePath($result['secure_url']);
                $course->setFilePublicId($result['public_id']);
            }

            // Validation
            $errors = $this->validator->validate($course);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->documentManager->flush();

            return $this->json([
                '@id' => '/api/courses/' . $course->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'content' => $course->getContent(),
                'filePath' => $course->getFilePath(),
                'classroom' => $course->getClassroom() ? '/api/classrooms/' . $course->getClassroom()->getId() : null,
                'createdAt' => $course->getCreatedAt()?->format('c'),
                'updatedAt' => $course->getUpdatedAt()?->format('c'),
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Suppression du fichier de support de cours
    #[Route('/courses/{id}/file', name: 'delete_course_file', methods: ['DELETE'])]
    public function deleteFile(string $id): JsonResponse
    {
        try {
            $course = $this->documentManager->getRepository(Course::class)->find($id);

            if (!$course) {
                return $this->json(['error' => 'Cours introuvable'], Response::HTTP_NOT_FOUND);
            }

            if ($course->getFilePublicId()) {
                $this->cloudinaryService->deleteFile($course->getFilePublicId());
            }

            $course->setFilePath(null);
            $course->setFilePublicId(null);
            $this->documentManager->flush();

            return $this->json(['message' => 'Fichier supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
