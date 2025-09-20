<?php

namespace App\DataFixtures;

use App\Document\User;
use Doctrine\Bundle\MongoDBBundle\Fixture\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
  private UserPasswordHasherInterface $passwordHasher;

  public function __construct(UserPasswordHasherInterface $passwordHasher)
  {
    $this->passwordHasher = $passwordHasher;
  }

  public function load(ObjectManager $manager): void
  {
    // ✅ Administrateur
    $admin = new User();
    $admin->setEmail('admin@acadyo.com');
    $admin->setName('Administrateur');
    $admin->setRoles(['ROLE_ADMIN', 'ROLE_TEACHER']);
    $admin->setPassword(
      $this->passwordHasher->hashPassword($admin, 'password')
    );
    $manager->persist($admin);

    // ✅ Professeur classique déjà en BDD
    $userTeacher = new User();
    $userTeacher->setEmail('formateur@test.com');
    $userTeacher->setName('formateur');
    $userTeacher->setRoles(['ROLE_TEACHER']);
    $userTeacher->setPassword(
      $this->passwordHasher->hashPassword($userTeacher, 'password123')
    );
    $manager->persist($userTeacher);

    // ✅ Etudiant classique déjà en BDD
    $userStudent = new User();
    $userStudent->setEmail('étudiant@test.com');
    $userStudent->setName('étudiant');
    $userStudent->setRoles(['ROLE_STUDENT']);
    $userStudent->setPassword(
      $this->passwordHasher->hashPassword($userStudent, 'password123')
    );
    $manager->persist($userStudent);

    // ✅ Utilisateur simple pour les tests de connexion
    $simpleUser = new User();
    $simpleUser->setEmail('user@test.com');
    $simpleUser->setName('Utilisateur Simple');
    $simpleUser->setRoles(['ROLE_USER']);
    $simpleUser->setPassword(
      $this->passwordHasher->hashPassword($simpleUser, 'password')
    );
    $manager->persist($simpleUser);

    // ✅ Utilisateur avec token valide
    $userWithValidToken = new User();
    $userWithValidToken->setEmail('reset@test.com');
    $userWithValidToken->setName('ResetUser');
    $userWithValidToken->setPassword(
      $this->passwordHasher->hashPassword($userWithValidToken, 'oldPassword')
    );
    $userWithValidToken->setResetToken('valid_token');
    $userWithValidToken->setResetTokenExpiresAt(new \DateTime('+1 hour')); // token valide
    $manager->persist($userWithValidToken);

    // ✅ Utilisateur avec token expiré (optionnel)
    $userWithExpiredToken = new User();
    $userWithExpiredToken->setEmail('expired@test.com');
    $userWithExpiredToken->setName('ExpiredUser');
    $userWithExpiredToken->setPassword(
      $this->passwordHasher->hashPassword($userWithExpiredToken, 'oldPassword')
    );
    $userWithExpiredToken->setResetToken('expired_token');
    $userWithExpiredToken->setResetTokenExpiresAt(new \DateTime('-1 hour')); // déjà expiré
    $manager->persist($userWithExpiredToken);

    $manager->flush();
  }
}