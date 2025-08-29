<?php

namespace App\Controller;

use App\Document\Classroom;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route("/api")]

final class ClassroomController extends AbstractController
{

    #[Route('/classroom', name: 'create_classroom', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm, #[CurrentUser] $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifie que l'utilisateur est connecté et est formateur

        if (!$user || !in_array('ROLE_TEACHER', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $course = new Classroom();
        $course->setTitle($data['title'] ?? 'Untitled');
        $course->setDescription($data['description'] ?? '');
        $course->setTeacher($user);

        // 🔐 Génère le code unique
        $code = $this->generateUniqueCode($dm);
        $course->setCode($code);

        $dm->persist($course);
        $dm->flush();

        return $this->json([
            'message' => 'Classroom créé avec succès',
            'classroom' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
            ]
        ], 200);
    }

    #[Route('/classrooms/{id}', name: 'update_classroom', methods: ['PUT'])]
    public function update(Classroom $classroom, Request $request, DocumentManager $dm, #[CurrentUser] $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$user || !in_array('teacher', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Optionnel : vérifier que l'utilisateur est bien le créateur du classroom.
        if ($classroom->getTeacher()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Vous n\'êtes pas le créateur de ce classroom.'], 403);
        }

        if (isset($data['title'])) {
            $classroom->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $classroom->setDescription($data['description']);
        }

        $dm->persist($classroom);
        $dm->flush();

        return $this->json([
            'message' => 'Classroom mis à jour avec succès',
            'classroom' => [
                'id' => $classroom->getId(),
                'title' => $classroom->getTitle(),
                'description' => $classroom->getDescription(),
                'code' => $classroom->getCode(),
            ]
        ]);
    }

    private function generateUniqueCode(DocumentManager $dm, int $length = 6): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes($length / 2))); // Ex: '4A3F1C'
            $existingClassroom = $dm->getRepository(Classroom::class)->findOneBy(['code' => $code]);
        } while ($existingClassroom !== null);

        return $code;
    }
}
