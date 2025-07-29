<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SubmissionRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document(repositoryClass: SubmissionRepository::class)]
#[ApiResource]
class Submission
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, inversedBy: 'submissions')]
    private ?User $student = null;

    #[ODM\ReferenceOne(targetDocument: Assignment::class, inversedBy: 'submissions')]
    private ?Assignment $assignment = null;

    #[ODM\Field(type: Type::COLLECTION, nullable: true)]
    private array $files = [];

    #[ODM\Field(nullable: true)]
    private ?string $comment = null;

    #[ODM\Field(nullable: true)]
    private ?int $grade = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $submittedAt = null;

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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(?array $files): static
    {
        $this->files = $files;

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
}
