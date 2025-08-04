<?php

namespace App\Controller;

use App\Document\Course;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api")]
final class CourseController extends AbstractController
{
    /**
     * @return User
     */
    private function getCurrentUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user;
    }

    #[Route('/courses', name: 'create_course', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifie que l'utilisateur est connecté et est formateur
        $user = $this->getCurrentUser();

        if (!$user || !in_array('ROLE_TEACHER', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $course = new Course();
        $course->setTitle($data['title'] ?? 'Untitled');
        $course->setDescription($data['description'] ?? '');
        $course->setTeacher($user);

        // 🔐 Génère le code unique
        $code = $this->generateUniqueCode($dm);
        $course->setCode($code);

        $dm->persist($course);
        $dm->flush();

        return $this->json([
            'message' => 'Cours créé avec succès',
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
            ]
        ]);
    }

    #[Route('/courses/{id}', name: 'update_course', methods: ['PUT'])]
    public function update(Course $course, Request $request, DocumentManager $dm): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $this->getCurrentUser();

        if (!$user || !in_array('teacher', $user->getRoles())) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Optionnel : vérifier que l'utilisateur est bien le créateur du cours
        if ($course->getTeacher()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Vous n\'êtes pas le créateur de ce cours'], 403);
        }

        if (isset($data['title'])) {
            $course->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $course->setDescription($data['description']);
        }

        $dm->persist($course);
        $dm->flush();

        return $this->json([
            'message' => 'Cours mis à jour avec succès',
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'code' => $course->getCode(),
            ]
        ]);
    }

    private function generateUniqueCode(DocumentManager $dm, int $length = 6): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes($length / 2))); // Ex: '4A3F1C'
            $existingCourse = $dm->getRepository(Course::class)->findOneBy(['code' => $code]);
        } while ($existingCourse !== null);

        return $code;
    }
}
