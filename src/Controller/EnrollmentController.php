<?php

namespace App\Controller;

use App\Document\Course;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/enrollment')]
class EnrollmentController extends AbstractController
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

    public function __construct(
        private DocumentManager $dm
    ) {
    }

    /**
     * Rejoindre un cours avec un code
     */
    #[Route('/join', name: 'api_join_course', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function joinCourse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le code du cours est requis'
            ], 400);
        }

        $code = strtoupper(trim($code));
        $user = $this->getCurrentUser();

        // Rechercher le cours par code
        $course = $this->dm->getRepository(Course::class)->findOneBy(['code' => $code]);

        if (!$course) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Code invalide ou cours inexistant'
            ], 404);
        }

        // Vérifier si l'étudiant n'est pas déjà inscrit
        if ($course->hasStudent($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous êtes déjà inscrit à ce cours'
            ], 409);
        }

        // Vérifier que l'utilisateur n'est pas le professeur du cours
        if ($course->getTeacher() === $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous inscrire à votre propre cours'
            ], 400);
        }

        // Inscription
        $course->addStudent($user);
        $user->addEnrolledCourse($course);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Inscription réussie !',
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'code' => $course->getCode(),
                'teacher' => [
                    'id' => $course->getTeacher()->getId(),
                    'name' => $course->getTeacher()->getName(),
                    'email' => $course->getTeacher()->getEmail()
                ],
                'studentsCount' => $course->getStudentsCount(),
                'createdAt' => $course->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Quitter un cours
     */
    #[Route('/leave/{courseId}', name: 'api_leave_course', methods: ['DELETE'])]
    #[IsGranted('ROLE_STUDENT')]
    public function leaveCourse(string $courseId): JsonResponse
    {
        $user = $this->getCurrentUser();
        $course = $this->dm->getRepository(Course::class)->find($courseId);

        if (!$course) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        }

        if (!$course->hasStudent($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'êtes pas inscrit à ce cours'
            ], 400);
        }

        $course->removeStudent($user);
        $user->removeEnrolledCourse($course);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Vous avez quitté le cours avec succès'
        ]);
    }

    /**
     * Obtenir les cours de l'étudiant connecté
     */
    #[Route('/my-courses', name: 'api_my_enrolled_courses', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function getMyEnrolledCourses(): JsonResponse
    {
        $user = $this->getCurrentUser();
        $courses = $user->getEnrolledCourses();

        $coursesData = [];
        foreach ($courses as $course) {
            $coursesData[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'code' => $course->getCode(),
                'teacher' => [
                    'id' => $course->getTeacher()->getId(),
                    'name' => $course->getTeacher()->getName(),
                    'email' => $course->getTeacher()->getEmail()
                ],
                'studentsCount' => $course->getStudentsCount(),
                'lessonsCount' => $course->getLessons()->count(),
                'assignmentsCount' => $course->getAssignments()->count(),
                'createdAt' => $course->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'success' => true,
            'courses' => $coursesData,
            'totalCourses' => count($coursesData)
        ]);
    }

    /**
     * Obtenir les étudiants d'un cours
     */
    #[Route('/course/{courseId}/students', name: 'api_course_students', methods: ['GET'])]
    public function getCourseStudents(string $courseId): JsonResponse
    {
        $user = $this->getUser();
        $course = $this->dm->getRepository(Course::class)->find($courseId);

        if (!$course) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cours non trouvé'
            ], 404);
        }

        $students = $course->getStudents();
        $studentsData = [];

        foreach ($students as $student) {
            $studentsData[] = [
                'id' => $student->getId(),
                'name' => $student->getName(),
                'email' => $student->getEmail(),
                'enrolledAt' => $student->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $teacher = $course->getTeacher();

        return new JsonResponse([
            'success' => true,
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
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
     * Retirer un étudiant d'un cours (pour le professeur)
     */
    #[Route('/course/{courseId}/remove-student/{studentId}', name: 'api_remove_student', methods: ['DELETE'])]
    #[IsGranted('ROLE_TEACHER')]
    public function removeStudentFromCourse(string $courseId, string $studentId): JsonResponse
    {
        $user = $this->getUser();
        $course = $this->dm->getRepository(Course::class)->find($courseId);
        $student = $this->dm->getRepository(User::class)->find($studentId);

        if (!$course || !$student) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cours ou étudiant non trouvé'
            ], 404);
        }

        // Vérifier que l'utilisateur est bien le professeur de ce cours
        if ($course->getTeacher() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        if (!$course->hasStudent($student)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet étudiant n\'est pas inscrit à ce cours'
            ], 400);
        }

        $course->removeStudent($student);
        $student->removeEnrolledCourse($course);

        $this->dm->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Étudiant retiré du cours avec succès'
        ]);
    }
}