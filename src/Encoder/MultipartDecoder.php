<?php

namespace App\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use function is_array;

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

    return array_map(static function ($element) {
      // Try to decode JSON strings
      $decoded = json_decode($element, true);

      if (is_array($decoded)) {
        return $decoded;
      }

      // Handle boolean conversion
      if ($element === 'true') {
        return true;
      }
      if ($element === 'false') {
        return false;
      }

      return $element;
    }, $request->request->all()) + $request->files->all();
  }

  public function supportsDecoding(string $format): bool
  {
    return self::FORMAT === $format;
  }
}