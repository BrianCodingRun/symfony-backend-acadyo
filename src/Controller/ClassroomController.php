<?php

namespace App\Controller;

use App\Document\Classroom;
use App\Repository\ClassroomRepository;
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
    public function create(Request $request, DocumentManager $dm, #[CurrentUser] $user, ClassroomRepository $classroomRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifie que l'utilisateur est connecté et est formateur
        if (!$user || !in_array('ROLE_TEACHER', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if (!isset($data['title'])) {
            return $this->json(['error' => 'Title is require !'], 400);
        }

        $existingClassroom = $classroomRepository->findOneBy(['title' => $data['title']]);
        if ($existingClassroom) {
            return $this->json(['error' => 'Classroom already existing'], 409);
        }

        $classroom = new Classroom();
        $classroom->setTitle($data['title'] ?? 'Untitled');
        $classroom->setDescription($data['description'] ?? '');
        $classroom->setTeacher($user);

        // 🔐 Génère le code unique
        $code = $this->generateUniqueCode($dm);
        $classroom->setCode($code);

        $dm->persist($classroom);
        $dm->flush();

        return $this->json([
            'message' => 'Classroom créé avec succès',
            'classroom' => [
                '@id' => '/api/classrooms/' . $classroom->getId(),
                'id' => $classroom->getId(),
                'title' => $classroom->getTitle(),
                'description' => $classroom->getDescription(),
                'code' => $classroom->getCode(),
            ]
        ], 201);
    }

    #[Route('/classrooms/{id}', name: 'update_classroom', methods: ['PUT'])]
    public function update(Classroom $classroom, Request $request, DocumentManager $dm, #[CurrentUser] $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$user || !in_array('ROLE_TEACHER', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
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
        ], 200);
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
