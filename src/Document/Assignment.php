<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AssignmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document(repositoryClass: AssignmentRepository::class)]
#[ApiResource]
class Assignment
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field]
    private ?string $title = null;

    #[ODM\Field(nullable: true)]
    private ?string $instruction = null;

    #[ODM\Field(type: Type::DATE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ODM\ReferenceOne(targetDocument: Course::class, inversedBy: 'assignments')]
    private ?Course $course = null;

    #[ODM\ReferenceMany(targetDocument: User::class, orphanRemoval: true, mappedBy: 'assignment')]
    private Collection $assignedTo;

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ODM\ReferenceMany(targetDocument: Submission::class, mappedBy: 'assignment')]
    private Collection $submissions;

    public function __construct()
    {
        $this->assignedTo = new ArrayCollection();
        $this->submissions = new ArrayCollection();
    }

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

    public function getInstruction(): ?string
    {
        return $this->instruction;
    }

    public function setInstruction(?string $instruction): static
    {
        $this->instruction = $instruction;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

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

    /**
     * @return Collection<int, User>
     */
    public function getAssignedTo(): Collection
    {
        return $this->assignedTo;
    }

    public function addAssignedTo(User $assignedTo): static
    {
        if (!$this->assignedTo->contains($assignedTo)) {
            $this->assignedTo->add($assignedTo);
            $assignedTo->setAssignment($this);
        }

        return $this;
    }

    public function removeAssignedTo(User $assignedTo): static
    {
        if ($this->assignedTo->removeElement($assignedTo)) {
            // set the owning side to null (unless already changed)
            if ($assignedTo->getAssignment() === $this) {
                $assignedTo->setAssignment(null);
            }
        }

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

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setAssignment($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getAssignment() === $this) {
                $submission->setAssignment(null);
            }
        }

        return $this;
    }
}
