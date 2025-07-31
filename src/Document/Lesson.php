<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\LessonRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ODM\Document(repositoryClass: LessonRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[ApiResource(
    types: ['https://schema.org/Book'],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['lesson:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['lesson:read']]
        ),
        new Delete()
    ]
)]
class Lesson
{
    #[ODM\Id]
    #[Groups(['lesson:read'])]
    private ?string $id = null;

    #[ODM\Field]
    #[Groups(['lesson:read'])]
    private ?string $title = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['lesson:read'])]
    private ?string $content = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['lesson:read'])]
    public ?string $filePath = null;

    #[ODM\ReferenceOne(targetDocument: Course::class, inversedBy: 'lessons')]
    #[Groups(['lesson:read'])]
    private ?Course $course = null;

    #[ODM\Field]
    #[Groups(['lesson:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    #[Groups(['lesson:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
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