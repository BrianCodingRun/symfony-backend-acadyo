<?php

namespace App\Command;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
  name: 'app:create-admin-user',
  description: 'Crée un utilisateur admin si inexistant'
)]
class CreateAdminUserCommand extends Command
{
  public function __construct(
    private DocumentManager $dm,
    private UserPasswordHasherInterface $passwordHasher
  ) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $name = $_ENV['ADMIN_NAME'] ?? 'Administrateur';
    $email = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
    $password = $_ENV['ADMIN_PASSWORD'] ?? 'changeMe123!';

    $repo = $this->dm->getRepository(User::class);
    $existingUser = $repo->findOneBy(['email' => $email]);

    if ($existingUser) {
      $output->writeln("<info>L'utilisateur admin '$email' existe déjà.</info>");
      return Command::SUCCESS;
    }

    $user = new User();
    $user->setName($name);
    $user->setEmail($email);
    $user->setRoles(['ROLE_ADMIN', 'ROLE_TEACHER']);
    $user->setPassword($this->passwordHasher->hashPassword($user, $password));

    $this->dm->persist($user);
    $this->dm->flush();

    $output->writeln("<info>Utilisateur admin '$email' créé avec succès.</info>");
    return Command::SUCCESS;
  }
}
