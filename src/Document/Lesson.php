<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LessonRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document(repositoryClass: LessonRepository::class)]
#[ApiResource]
class Lesson
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field]
    private ?string $title = null;

    #[ODM\Field(nullable: true)]
    private ?string $content = null;

    #[ODM\Field(type: Type::COLLECTION, nullable: true)]
    private array $files = [];

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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(?array $files): static
    {
        $this->files = $files;

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
}
