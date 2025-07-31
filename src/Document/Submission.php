<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SubmissionRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Component\Serializer\Annotation\Groups;

#[ODM\Document(repositoryClass: SubmissionRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[ApiResource(
    types: ['https://schema.org/Book'],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['submission:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['submission:read']]
        ),
        new Delete()
    ]
)]
class Submission
{
    #[ODM\Id]
    #[Groups(['submission:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, inversedBy: 'submissions')]
    #[Groups(['submission:read'])]
    private ?User $student = null;

    #[ODM\ReferenceOne(targetDocument: Assignment::class, inversedBy: 'submissions')]
    #[Groups(['submission:read'])]
    private ?Assignment $assignment = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['submission:read'])]
    public ?string $filePath = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['submission:read'])]
    private ?string $comment = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['submission:read'])]
    private ?int $grade = null;

    #[ODM\Field]
    #[Groups(['submission:read'])]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ODM\Field]
    #[Groups(['submission:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    #[Groups(['submission:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getAssignment(): ?Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(?Assignment $assignment): static
    {
        $this->assignment = $assignment;

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getGrade(): ?int
    {
        return $this->grade;
    }

    public function setGrade(?int $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

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
