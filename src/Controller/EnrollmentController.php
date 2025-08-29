<?php

namespace App\Controller;

use App\Document\Classroom;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/enrollment')]
class EnrollmentController extends AbstractController
{

    public function __construct(
        private DocumentManager $dm
    ) {
    }

    #[Route('/debug-user', name: 'api_debug_user', methods: ['GET'])]
    public function debugUser(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Aucun utilisateur détecté'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }


    /**
     * Rejoindre un cours avec un code
     */
    #[Route('/join', name: 'api_join_classroom', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function joinClassroom(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le code du classroom est requis'
            ], 400);
        }

        $code = strtoupper(trim($code));
        // $user = $this->getCurrentUser();

        // Rechercher le classroom par code
        $classroom = $this->dm->getRepository(Classroom::class)->findOneBy(['code' => $code]);

        if (!$classroom) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Code invalide ou classroom inexistant'
            ], 404);
        }

        // Vérifier si l'étudiant n'est pas déjà inscrit
        if ($classroom->hasStudent($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous êtes déjà inscrit à ce cours'
            ], 409);
        }

        // Vérifier que l'utilisateur n'est pas le professeur du cours
        if ($classroom->getTeacher() === $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous inscrire à votre propre cours'
            ], 400);
        }

        // Inscription
        $classroom->addStudent($user);
        $user->addEnrolledClassroom($classroom);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Inscription réussie !',
            'classroom' => [
                'id' => $classroom->getId(),
                'title' => $classroom->getTitle(),
                'description' => $classroom->getDescription(),
                'code' => $classroom->getCode(),
                'teacher' => [
                    'id' => $classroom->getTeacher()->getId(),
                    'name' => $classroom->getTeacher()->getName(),
                    'email' => $classroom->getTeacher()->getEmail()
                ],
                'studentsCount' => $classroom->getStudentsCount(),
                'createdAt' => $classroom->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Quitter un cours
     */
    #[Route('/leave/{classroomId}', name: 'api_leave_classroom', methods: ['DELETE'])]
    #[IsGranted('ROLE_STUDENT')]
    public function leaveClassroom(string $classroomId, #[CurrentUser] ?User $user): JsonResponse
    {
        $classroom = $this->dm->getRepository(Classroom::class)->find($classroomId);

        if (!$classroom) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        }

        if (!$classroom->hasStudent($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'êtes pas inscrit à ce cours'
            ], 400);
        }

        $classroom->removeStudent($user);
        $user->removeEnrolledClassroom($classroom);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Vous avez quitté le cours avec succès'
        ]);
    }

    /**
     * Obtenir les cours de l'étudiant connecté
     */
    #[Route('/my-classrooms', name: 'api_my_enrolled_classroom', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function getMyEnrolledClassrooms(#[CurrentUser] ?User $user): JsonResponse
    {
        $classrooms = $user->getEnrolledClassrooms();

        $classroomsData = [];
        foreach ($classrooms as $classroom) {
            $classroomsData[] = [
                'id' => $classroom->getId(),
                'title' => $classroom->getTitle(),
                'description' => $classroom->getDescription(),
                'code' => $classroom->getCode(),
                'teacher' => [
                    'id' => $classroom->getTeacher()->getId(),
                    'name' => $classroom->getTeacher()->getName(),
                    'email' => $classroom->getTeacher()->getEmail()
                ],
                'studentsCount' => $classroom->getStudentsCount(),
                'coursesCount' => $classroom->getCourses()->count(),
                'assignmentsCount' => $classroom->getAssignments()->count(),
                'createdAt' => $classroom->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'success' => true,
            'classrooms' => $classroomsData,
            'totalClassrooms' => count($classroomsData)
        ]);
    }

    /**
     * Obtenir les étudiants d'un cours
     */
    #[Route('/classroom/{classroomId}/students', name: 'api_classroom_students', methods: ['GET'])]
    public function getClassroomStudents(string $classroomId): JsonResponse
    {
        $classroom = $this->dm->getRepository(Classroom::class)->find($classroomId);

        if (!$classroom) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Classroom non trouvé'
            ], 404);
        }

        $students = $classroom->getStudents();
        $studentsData = [];

        foreach ($students as $student) {
            $studentsData[] = [
                'id' => $student->getId(),
                'name' => $student->getName(),
                'email' => $student->getEmail(),
                'enrolledAt' => $student->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $teacher = $classroom->getTeacher();

        return new JsonResponse([
            'success' => true,
            'classroom' => [
                'id' => $classroom->getId(),
                'title' => $classroom->getTitle(),
                'code' => $classroom->getCode(),
                'teacher' => [
                    'id' => $teacher->getId(),
                    'name' => $teacher->getName(),
                    'email' => $teacher->getEmail()
                ]
            ],
            'students' => $studentsData,
            'totalStudents' => count($studentsData)
        ]);
    }

    /**
     * Retirer un étudiant d'un classroom (pour le professeur)
     */
    #[Route('/classroom/{classroomId}/remove-student/{studentId}', name: 'api_remove_student', methods: ['DELETE'])]
    #[IsGranted('ROLE_TEACHER')]
    public function removeStudentFromClassroom(string $classroomId, string $studentId, #[CurrentUser] ?User $user): JsonResponse
    {
        $classroom = $this->dm->getRepository(Classroom::class)->find($classroomId);
        $student = $this->dm->getRepository(User::class)->find($studentId);

        if (!$classroom || !$student) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Classroom ou étudiant non trouvé.'
            ], 404);
        }

        // Vérifier que l'utilisateur est bien le professeur de ce classroom
        if ($classroom->getTeacher() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        if (!$classroom->hasStudent($student)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet étudiant n\'est pas inscrit dans ce classroom.'
            ], 400);
        }

        $classroom->removeStudent($student);
        $student->removeEnrolledClassroom($classroom);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Étudiant retiré du classroom avec succès.'
        ]);
    }
}