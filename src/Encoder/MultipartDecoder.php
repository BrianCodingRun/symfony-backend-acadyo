<?php

namespace App\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

final class MultipartDecoder implements DecoderInterface
{
  public const FORMAT = 'multipart';

  public function __construct(private RequestStack $requestStack)
  {
  }

  public function decode(string $data, string $format, array $context = []): ?array
  {
    $request = $this->requestStack->getCurrentRequest();

    if (!$request) {
      return null;
    }

    $decodedData = [];

    // Traiter les données de formulaire
    foreach ($request->request->all() as $key => $value) {
      // Essayer de décoder les chaînes JSON
      if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          $decodedData[$key] = $decoded;
          continue;
        }

        // Gérer les booléens
        if ($value === 'true') {
          $decodedData[$key] = true;
          continue;
        }
        if ($value === 'false') {
          $decodedData[$key] = false;
          continue;
        }
      }

      $decodedData[$key] = $value;
    }

    // Ajouter les fichiers uploadés
    foreach ($request->files->all() as $key => $file) {
      $decodedData[$key] = $file;
    }

    // IMPORTANT: Retourner les données même si vides pour que le decoder soit utilisé
    return $decodedData ?: [];
  }

  public function supportsDecoding(string $format): bool
  {
    return self::FORMAT === $format;
  }
}