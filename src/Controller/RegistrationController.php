<?php

namespace App\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api")]
final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        DocumentManager $dm
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['password'])) {
            return $this->json(['error' => 'Missing fields'], 400);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $dm->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);

        // Définir un rôle par défaut si non spécifié
        $role = $data['role'] ?? 'ROLE_USER';
        $user->setRole($role);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );
        $user->setPassword($hashedPassword);

        $dm->persist($user);
        $dm->flush();

        return $this->json([
            'message' => 'Utilisateur enregistré avec succès !',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'role' => $user->getRole()
            ]
        ], 201);
    }
}