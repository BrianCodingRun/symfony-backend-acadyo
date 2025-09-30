<?php
namespace App\Service;

use Cloudinary\Cloudinary;

class CloudinaryService
{
  private Cloudinary $cloudinary;

  // Types MIME autorisés
  private const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
    'text/plain',
  ];

  // Taille max (ici 1 Mo)
  private const MAX_FILE_SIZE = 1 * 1024 * 1024;

  public function __construct()
  {
    $this->cloudinary = new Cloudinary([
      'cloud' => [
        'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
        'api_key' => $_ENV['CLOUDINARY_API_KEY'],
        'api_secret' => $_ENV['CLOUDINARY_API_SECRET'],
      ],
      'url' => [
        'secure' => true
      ],
    ]);
  }

  public function uploadFile(string $filePath, array $options = []): array
  {
    // Vérification taille
    $fileSize = filesize($filePath);
    if ($fileSize > self::MAX_FILE_SIZE) {
      throw new \RuntimeException("Le fichier est trop volumineux (max " . (self::MAX_FILE_SIZE / 1024 / 1024) . " Mo).");
    }

    // Vérification type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
      throw new \RuntimeException("Type de fichier non autorisé ($mimeType).");
    }

    // Options par défaut pour forcer l'accès public
    $defaultOptions = [
      'type' => 'upload',
      'access_control' => [
        [
          'access_type' => 'anonymous'
        ]
      ],
    ];

    // Merge avec les options passées en paramètre
    $options = array_merge($defaultOptions, $options);

    return $this->cloudinary->uploadApi()->upload($filePath, $options)->getArrayCopy();
  }

  public function deleteFile(string $publicId): array
  {
    return $this->cloudinary->uploadApi()->destroy($publicId, [
      'resource_type' => 'raw',
      'type' => 'upload'
    ])->getArrayCopy();
  }

  // Méthode pour débloquer un fichier existant
  public function updateAccessControl(string $publicId): array
  {
    return $this->cloudinary->adminApi()->update($publicId, [
      'resource_type' => 'raw',
      'access_control' => [
        [
          'access_type' => 'anonymous'
        ]
      ]
    ])->getArrayCopy();
  }
}