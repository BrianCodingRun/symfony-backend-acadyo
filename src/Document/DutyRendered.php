<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\DutyRenderedRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ODM\Document(repositoryClass: DutyRenderedRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[ApiResource(
    types: ['https://schema.org/Book'],
    operations: [
        new Get(
            normalizationContext: ['groups' => ['dutyRendered:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['dutyRendered:read']]
        ),
        new Delete()
    ]
)]
class DutyRendered
{
    #[ODM\Id]
    #[Groups(['dutyRendered:read'])]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, inversedBy: 'dutysRendered')]
    #[Groups(['dutyRendered:read'])]
    private ?User $student = null;

    #[ODM\ReferenceOne(targetDocument: Assignment::class, inversedBy: 'dutysRendered')]
    #[Groups(['dutyRendered:read'])]
    private ?Assignment $assignment = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['dutyRendered:read'])]
    public ?string $filePath = null;

    /** @ODM\Field(type="string", nullable=true) */
    private ?string $filePublicId = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['dutyRendered:read'])]
    private ?string $comment = null;

    #[ODM\Field(nullable: true)]
    #[Groups(['dutyRendered:read'])]
    private ?int $grade = null;

    #[ODM\Field]
    #[Groups(['dutyRendered:read'])]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ODM\Field]
    #[Groups(['dutyRendered:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    #[Groups(['dutyRendered:read'])]
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

    public function getFilePublicId(): ?string
    {
        return $this->filePublicId;
    }

    public function setFilePublicId(?string $filePublicId): self
    {
        $this->filePublicId = $filePublicId;
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
