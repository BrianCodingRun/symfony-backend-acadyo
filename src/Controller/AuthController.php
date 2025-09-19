<?php
namespace App\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api")]
class AuthController extends AbstractController
{
    #[Route('/request-reset-password', name: 'request_reset_password', methods: ['POST'])]
    public function requestResetPassword(
        Request $request,
        DocumentManager $dm,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email manquant'], 400);
        }

        /** @var User|null $user */
        $user = $dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Pour ne pas révéler si un email existe ou pas
            return $this->json(['message' => 'Si un compte existe, un email sera envoyé.'], 200);
        }

        // Génération token + expiration
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt((new \DateTime())->modify('+1 hour'));

        $dm->flush();

        // Envoi email
        $resetLink = "http://localhost:5173/reset-password?token=$token";
        $mailer->send(
            (new Email())
                ->from("bcbcoupama@gmail.com")
                ->to($user->getEmail())
                ->subject('Réinitialisation de mot de passe')
                ->text("Cliquez sur ce lien pour réinitialiser votre mot de passe : $resetLink")
        );

        return $this->json(['message' => 'Si un compte existe, le lien de réinitialisation de votre mot de passe sera envoyé à cette adresse email.'], 200);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        DocumentManager $dm,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$token || !$newPassword) {
            return $this->json(['error' => 'Token ou mot de passe manquant'], 400);
        }

        /** @var User|null $user */
        $user = $dm->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTime()) {
            return $this->json(['error' => 'Token invalide ou expiré'], 400);
        }

        // Mise à jour du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $dm->flush();

        return $this->json(['message' => 'Mot de passe mis à jour avec succès']);
    }

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

        // Role étudiant attribué par défaut
        $user->setRoles(['ROLE_STUDENT']);

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
                'role' => $user->getRoles()
            ]
        ], 201);
    }
}
