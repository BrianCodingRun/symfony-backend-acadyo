<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class TestMailerController extends AbstractController
{
    #[Route('/email', name: 'app_test_mailer')]
    public function sendMailer(MailerInterface $mailer): JsonResponse
    {
        $email = (new Email())
            ->from("bcbcoupama@gmail.com")
            ->to('bcbcoupama@gmail.com')
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');
        $mailer->send($email);
        return new JsonResponse(["test" => true]);
    }
}
