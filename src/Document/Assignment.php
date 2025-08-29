<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AssignmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document(repositoryClass: AssignmentRepository::class)]
#[ODM\HasLifecycleCallbacks]
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

    #[ODM\ReferenceOne(targetDocument: Classroom::class, inversedBy: 'assignments')]
    private ?Classroom $classroom = null;

    // CORRECTION: Relation Many-to-Many avec inversedBy au lieu de mappedBy
    #[ODM\ReferenceMany(targetDocument: User::class, inversedBy: 'assignments')]
    private Collection $assignedTo;

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ODM\ReferenceMany(targetDocument: DutyRendered::class, mappedBy: 'assignment')]
    private Collection $dutysRendered;

    public function __construct()
    {
        $this->assignedTo = new ArrayCollection();
        $this->dutysRendered = new ArrayCollection();
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

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): static
    {
        $this->classroom = $classroom;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAssignedTo(): Collection
    {
        return $this->assignedTo;
    }

    // CORRECTION: Méthodes add/remove pour relation Many-to-Many
    public function addAssignedTo(User $assignedTo): static
    {
        if (!$this->assignedTo->contains($assignedTo)) {
            $this->assignedTo->add($assignedTo);
            $assignedTo->addAssignment($this); // addAssignment au lieu de setAssignment
        }

        return $this;
    }

    public function removeAssignedTo(User $assignedTo): static
    {
        if ($this->assignedTo->removeElement($assignedTo)) {
            $assignedTo->removeAssignment($this); // removeAssignment au lieu de setAssignment(null)
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
     * @return Collection<int, DutyRendered>
     */
    public function getDutyRendered(): Collection
    {
        return $this->dutysRendered;
    }

    public function addDutyRendered(DutyRendered $dutyRendered): static
    {
        if (!$this->dutysRendered->contains($dutyRendered)) {
            $this->dutysRendered->add($dutyRendered);
            $dutyRendered->setAssignment($this);
        }

        return $this;
    }

    public function removeDutyRendered(DutyRendered $dutyRendered): static
    {
        if ($this->dutysRendered->removeElement($dutyRendered)) {
            // set the owning side to null (unless already changed)
            if ($dutyRendered->getAssignment() === $this) {
                $dutyRendered->setAssignment(null);
            }
        }

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