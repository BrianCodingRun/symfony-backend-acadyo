<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\HttpFoundation\File\File;
use App\Repository\LessonRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ODM\Document(repositoryClass: LessonRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    types: ['https://schema.org/Book'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            outputFormats: ['jsonld' => ['application/ld+json']],
            inputFormats: ['multipart' => ['multipart/form-data']],
        ),
        new Put(
            inputFormats: ['multipart' => ['multipart/form-data']],
        )
    ]
)]
class Lesson
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field]
    private ?string $title = null;

    #[ODM\Field(nullable: true)]
    private ?string $content = null;

    #[Vich\UploadableField(
        mapping: 'media_object',
        fileNameProperty: 'filePath'
    )]
    public ?File $file = null;

    #[ODM\Field(nullable: true)]
    public ?string $filePath = null;

    #[ODM\ReferenceOne(targetDocument: Course::class, inversedBy: 'lessons')]
    private ?Course $course = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ODM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ODM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

}
