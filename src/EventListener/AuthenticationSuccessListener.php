<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

final class AuthenticationSuccessListener
{
  public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
  {
    $data = $event->getData();
    $user = $event->getUser();

    if (!$user) {
      return;
    }

    // Exemple d'ajout d'informations
    $data['user'] = [
      'id' => $user->getId(),
      'name' => $user->getName(),
      'email' => $user->getEmail(),
      'roles' => $user->getRoles(),
    ];

    $event->setData($data);
  }
}
