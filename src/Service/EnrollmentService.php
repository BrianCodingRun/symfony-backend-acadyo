<?php

namespace App\Service;

use App\Document\Course;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;

class EnrollmentService
{
  public function __construct(
    private DocumentManager $dm
  ) {
  }

  public function enrollStudentByCourse(Course $course, User $student): array
  {
    // Vérifications
    if (!$student->hasRole('ROLE_STUDENT')) {
      return ['success' => false, 'message' => 'Seuls les étudiants peuvent s\'inscrire'];
    }

    if ($course->getTeacher() === $student) {
      return ['success' => false, 'message' => 'Vous ne pouvez pas vous inscrire à votre propre cours'];
    }

    if ($course->hasStudent($student)) {
      return ['success' => false, 'message' => 'Étudiant déjà inscrit'];
    }

    // Inscription
    $course->addStudent($student);
    $student->addEnrolledCourse($course);

    $this->dm->flush();

    return [
      'success' => true,
      'message' => 'Inscription réussie',
      'course' => $course
    ];
  }

  public function enrollStudentByCode(string $code, User $student): array
  {
    $course = $this->dm->getRepository(Course::class)->findOneBy(['code' => $code]);

    if (!$course) {
      return ['success' => false, 'message' => 'Code invalide'];
    }

    return $this->enrollStudentByCourse($course, $student);
  }

  public function unenrollStudent(Course $course, User $student): array
  {
    if (!$course->hasStudent($student)) {
      return ['success' => false, 'message' => 'Étudiant non inscrit à ce cours'];
    }

    $course->removeStudent($student);
    $student->removeEnrolledCourse($course);

    $this->dm->flush();

    return ['success' => true, 'message' => 'Désinscription réussie'];
  }

  public function getStudentCourses(User $student): array
  {
    return $student->getEnrolledCourses()->toArray();
  }

  public function getCourseStudents(Course $course): array
  {
    return $course->getStudents()->toArray();
  }

  public function generateUniqueCode(): string
  {
    do {
      $code = $this->generateRandomCode();
      $existing = $this->dm->getRepository(Course::class)->findOneBy(['code' => $code]);
    } while ($existing);

    return $code;
  }

  private function generateRandomCode(int $length = 6): string
  {
    $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'; // Évite O, 0 pour la lisibilité
    $code = '';

    for ($i = 0; $i < $length; $i++) {
      $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
  }
}