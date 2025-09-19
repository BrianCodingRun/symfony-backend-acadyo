<?php
namespace App\Service;

use Cloudinary\Cloudinary;

class CloudinaryService
{
  private Cloudinary $cloudinary;

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
    return $this->cloudinary->uploadApi()->upload($filePath, $options)->getArrayCopy();
  }
  public function deleteFile(string $publicId): array
  {
    return $this->cloudinary->uploadApi()->destroy($publicId)->getArrayCopy();
  }
}