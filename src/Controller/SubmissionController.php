<?php

namespace App\Controller;

use App\Document\Assignment;
use App\Document\Submission;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route("/api")]
final class SubmissionController extends AbstractController
{
    public function __construct(
        private DocumentManager $documentManager,
        private SluggerInterface $slugger,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/submissions', name: 'create_submission', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $submission = new Submission();

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
                $submission->setStudent($user);
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
                $submission->setAssignment($assignment);
            } else {
                return $this->json(['error' => 'Le devoir est introuvable'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(["error" => "ID du devoir à rendre obligatoire"], Response::HTTP_BAD_REQUEST);
        }

        if ($file) {
            $fileName = $this->handleFileUpload($file);
            if (!$fileName) {
                return $this->json(['error' => 'Erreur lors de l\'upload du fichier'], Response::HTTP_BAD_REQUEST);
            }
            $submission->setFilePath($fileName);
        }

        if ($comment) {
            $submission->setComment($comment);
        }
        if ($grade) {
            $submission->setGrade($grade);
        }

        if (!$submittedAtString) {
            return $this->json(['error' => 'La date de soumission du devoir est obligatoire'], Response::HTTP_BAD_REQUEST);
        }
        $submittedAt = new \DateTimeImmutable($submittedAtString);
        $submission->setSubmittedAt($submittedAt);

        // Validation avec Symfony Validator
        $errors = $this->validator->validate($submission);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Persister la soumission
        $this->documentManager->persist($submission);
        $this->documentManager->flush();

        return $this->json([
            'message' => 'Devoir rendu avec succès!',
            '@context' => '/api/contexts/Submissions',
            '@id' => '/api/submissions/' . $submission->getId(),
            '@type' => 'https://schema.org/Book',
            'id' => $submission->getId(),
            'filePath' => $submission->getFilePath(),
            'assignment' => $submission->getAssignment() ? '/api/assignments/' . $submission->getAssignment()->getId() : null,
            'createdAt' => $submission->getCreatedAt()?->format('c'),
            'updatedAt' => $submission->getUpdatedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route("/submissions/{id}", name: 'update submission', methods: ['POST'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $submission = $this->documentManager->getRepository(Submission::class)->find($id);
            if (!$submission) {
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
                        $submission->setStudent($user);
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
                        $submission->setAssignment($assignment);
                    } else {
                        return $this->json(['error' => 'Le devoir est introuvable'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Gestion du fichier
            if ($file) {
                // Supprimer l'ancien fichier si il existe
                if ($submission->getFilePath()) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/submissions/' . $submission->getFilePath();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $fileName = $this->handleFileUpload($file);
                if (!$fileName) {
                    return $this->json(['error' => 'Erreur lors de l\'upload du fichier'], Response::HTTP_BAD_REQUEST);
                }
                $submission->setFilePath($fileName);
            }

            if ($comment !== null) {
                $submission->setComment($comment);
            }

            if ($grade !== null) {
                $submission->setGrade($grade);
            }

            if (!$submittedAtString) {
                return $this->json(['error' => 'La date de soumission du devoir est obligatoire'], Response::HTTP_BAD_REQUEST);
            }
            $submittedAt = new \DateTimeImmutable($submittedAtString);
            $submission->setSubmittedAt($submittedAt);

            // Validation de l'entité
            $errors = $this->validator->validate($submission);
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
                '@context' => '/api/contexts/Submissions',
                '@id' => '/api/submissions/' . $submission->getId(),
                '@type' => 'https://schema.org/Book',
                'id' => $submission->getId(),
                'filePath' => $submission->getFilePath(),
                'assignment' => $submission->getAssignment() ? '/api/assignments/' . $submission->getAssignment()->getId() : null,
                'createdAt' => $submission->getCreatedAt()?->format('c'),
                'updatedAt' => $submission->getUpdatedAt()?->format('c'),
            ]);
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
                $this->getParameter('kernel.project_dir') . '/public/uploads/submissions',
                $fileName
            );
            return $fileName;
        } catch (FileException $e) {
            return null;
        }
    }
}
