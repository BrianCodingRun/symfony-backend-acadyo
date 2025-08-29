<?php

namespace App\Controller;

use App\Document\Assignment;
use App\Document\DutyRendered;
use App\Document\User;
use App\Service\CloudinaryService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route("/api")]
final class DutyRenderedController extends AbstractController
{
    public function __construct(
        private DocumentManager $documentManager,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator,
        private CloudinaryService $cloudinaryService
    ) {
    }

    #[Route('/dutyRendered', name: 'create_dutyRendered', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dutyRendered = new DutyRendered();

        // Get data
        $studentID = $request->request->get('student');
        $assignmentID = $request->request->get('assignment');
        $file = $request->files->get('file');
        $comment = $request->request->get('comment');
        $grade = $request->request->get('grade');
        $submittedAtString = $request->request->get('submittedAt');

        if ($studentID) {
            // Nettoyer l'ID si c'est un IRI
            if (str_starts_with($studentID, '/api/users/')) {
                $studentID = str_replace('/api/users/', '', $studentID);
            }
            $user = $this->documentManager->getRepository(User::class)->find($studentID);
            if ($user) {
                $dutyRendered->setStudent($user);
            } else {
                return $this->json(['error' => 'Etudiant introuvable'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(["error" => "ID de l'étudiant obligatoire !"], Response::HTTP_BAD_REQUEST);
        }

        if ($assignmentID) {
            if (str_starts_with($assignmentID, '/api/assignments/')) {
                $assignmentID = str_replace('/api/assignments/', '', $assignmentID);
            }
            $assignment = $this->documentManager->getRepository(Assignment::class)->find($assignmentID);
            if ($assignment) {
                $dutyRendered->setAssignment($assignment);
            } else {
                return $this->json(['error' => 'Le devoir est introuvable'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(["error" => "ID du devoir à rendre obligatoire"], Response::HTTP_BAD_REQUEST);
        }

        if ($file) {
            $result = $this->cloudinaryService->uploadFile($file->getPathname(), [
                'folder' => 'acadyo/DutyRendered',
                'resource_type' => 'auto',
            ]);

            if (!$result || !isset($result['secure_url'])) {
                return $this->json(['error' => 'Erreur lors de l\'upload sur Cloudinary'], Response::HTTP_BAD_REQUEST);
            }

            $dutyRendered->setFilePath($result['secure_url']);
            $dutyRendered->setFilePublicId($result['public_id']);
        }

        if ($comment) {
            $dutyRendered->setComment($comment);
        }
        if ($grade) {
            $dutyRendered->setGrade($grade);
        }

        if (!$submittedAtString) {
            return $this->json(['error' => 'La date de soumission du devoir est obligatoire'], Response::HTTP_BAD_REQUEST);
        }
        $submittedAt = new \DateTimeImmutable($submittedAtString);
        $dutyRendered->setSubmittedAt($submittedAt);

        // Validation avec Symfony Validator
        $errors = $this->validator->validate($dutyRendered);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Persister la soumission
        $this->documentManager->persist($dutyRendered);
        $this->documentManager->flush();

        return $this->json([
            'message' => 'Devoir rendu avec succès!',
            '@id' => '/api/dutysRendered/' . $dutyRendered->getId(),
            '@type' => 'https://schema.org/Book',
            'id' => $dutyRendered->getId(),
            'filePath' => $dutyRendered->getFilePath(),
            'assignment' => $dutyRendered->getAssignment() ? '/api/assignments/' . $dutyRendered->getAssignment()->getId() : null,
            'createdAt' => $dutyRendered->getCreatedAt()?->format('c'),
            'updatedAt' => $dutyRendered->getUpdatedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route("/dutyRendered/{id}", name: 'update dutyRendered', methods: ['POST'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $dutyRendered = $this->documentManager->getRepository(DutyRendered::class)->find($id);
            if (!$dutyRendered) {
                return $this->json(['error' => 'Aucun devoir n\'a encore été soumis !'], Response::HTTP_NOT_FOUND);
            }

            // Get form data
            $studentID = $request->request->get('student');
            $assignmentID = $request->request->get('assignment');
            $file = $request->files->get('file');
            $comment = $request->request->get('comment');
            $grade = $request->request->get('grade');
            $submittedAtString = $request->request->get('submittedAt');

            if ($studentID !== null) {
                if ($studentID === '') {
                    return $this->json(["error" => "ID de l'étudiant obligatoire !"], Response::HTTP_BAD_REQUEST);
                } else {
                    // Nettoyer l'ID si c'est un IRI
                    if (str_starts_with($studentID, '/api/users/')) {
                        $studentID = str_replace('/api/users/', '', $studentID);
                    }
                    $user = $this->documentManager->getRepository(User::class)->find($studentID);
                    if ($user) {
                        $dutyRendered->setStudent($user);
                    } else {
                        return $this->json(['error' => 'Etudiant introuvable'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }
            if ($assignmentID !== null) {
                if ($assignmentID === '') {
                    return $this->json(["error" => "ID de l'étudiant obligatoire !"], Response::HTTP_BAD_REQUEST);
                } else {
                    // Nettoyer l'ID si c'est un IRI
                    if (str_starts_with($assignmentID, '/api/assignments/')) {
                        $assignmentID = str_replace('/api/assignments/', '', $assignmentID);
                    }
                    $assignment = $this->documentManager->getRepository(Assignment::class)->find($assignmentID);
                    if ($assignment) {
                        $dutyRendered->setAssignment($assignment);
                    } else {
                        return $this->json(['error' => 'Le devoir est introuvable'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Gestion du fichier
            if ($file) {
                // Supprimer ancien fichier Cloudinary
                if ($dutyRendered->getFilePublicId()) {
                    $this->cloudinaryService->deleteFile($dutyRendered->getFilePublicId());
                }

                $result = $this->cloudinaryService->uploadFile($file->getPathname(), [
                    'folder' => 'acadyo/dutyRendered',
                    'resource_type' => 'auto',
                ]);

                if (!$result || !isset($result['secure_url'])) {
                    return $this->json(['error' => 'Erreur lors de l\'upload sur Cloudinary'], Response::HTTP_BAD_REQUEST);
                }

                $dutyRendered->setFilePath($result['secure_url']);
                $dutyRendered->setFilePublicId($result['public_id']);
            }

            if ($comment !== null) {
                $dutyRendered->setComment($comment);
            }

            if ($grade !== null) {
                $dutyRendered->setGrade($grade);
            }

            if (!$submittedAtString) {
                return $this->json(['error' => 'La date de soumission du devoir est obligatoire'], Response::HTTP_BAD_REQUEST);
            }
            $submittedAt = new \DateTimeImmutable($submittedAtString);
            $dutyRendered->setSubmittedAt($submittedAt);

            // Validation de l'entité
            $errors = $this->validator->validate($dutyRendered);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->documentManager->flush();

            return $this->json([
                'message' => 'Rendu de devoir mise à jour avec succès!',
                '@id' => '/api/dutysRendered/' . $dutyRendered->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $dutyRendered->getId(),
                'filePath' => $dutyRendered->getFilePath(),
                'assignment' => $dutyRendered->getAssignment() ? '/api/assignments/' . $dutyRendered->getAssignment()->getId() : null,
                'createdAt' => $dutyRendered->getCreatedAt()?->format('c'),
                'updatedAt' => $dutyRendered->getUpdatedAt()?->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur interne du serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
