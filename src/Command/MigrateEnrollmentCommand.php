<?php

namespace App\Command;

use App\Document\Course;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'app:migrate-enrollment',
  description: 'Migre les anciennes données d\'inscription des étudiants'
)]
class MigrateEnrollmentCommand extends Command
{
  public function __construct(
    private DocumentManager $dm
  ) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $io->title('Migration des inscriptions d\'étudiants');

    // 1. Migrer les rôles des utilisateurs
    $this->migrateUserRoles($io);

    // 2. Migrer les relations d'inscription
    $this->migrateEnrollments($io);

    $io->success('Migration terminée avec succès !');

    return Command::SUCCESS;
  }

  private function migrateUserRoles(SymfonyStyle $io): void
  {
    $io->section('Migration des rôles utilisateur');

    $users = $this->dm->getRepository(User::class)->findAll();
    $migrated = 0;

    foreach ($users as $user) {
      // Si l'utilisateur a un ancien champ 'role' (string)
      if (method_exists($user, 'getRole') && $user->getRole()) {
        $oldRole = $user->getRole();

        // Convertir en nouveau format
        switch ($oldRole) {
          case 'teacher':
            $user->setRoles(['ROLE_TEACHER']);
            break;
          case 'student':
            $user->setRoles(['ROLE_STUDENT']);
            break;
          default:
            $user->setRoles(['ROLE_USER']);
        }

        $migrated++;
      }
    }

    $this->dm->flush();
    $io->text("✅ {$migrated} utilisateurs migrés");
  }

  private function migrateEnrollments(SymfonyStyle $io): void
  {
    $io->section('Migration des inscriptions aux cours');

    $courses = $this->dm->getRepository(Course::class)->findAll();
    $totalEnrollments = 0;

    foreach ($courses as $course) {
      // Si le cours a encore un array d'IDs d'étudiants
      if (method_exists($course, 'getStudents') && is_array($course->getStudents())) {
        $studentIds = $course->getStudents();

        // Convertir les IDs en objets User
        foreach ($studentIds as $studentId) {
          $student = $this->dm->getRepository(User::class)->find($studentId);

          if ($student && $student->hasRole('ROLE_STUDENT')) {
            // Ajouter la relation bidirectionnelle
            $course->addStudent($student);
            $student->addEnrolledCourse($course);
            $totalEnrollments++;
          }
        }
      }
    }

    $this->dm->flush();
    $io->text("✅ {$totalEnrollments} inscriptions migrées");
  }
}