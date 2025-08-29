<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ODM\Document(repositoryClass: UserRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[ApiResource]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field]
    private ?string $name = null;

    #[ODM\Field]
    private ?string $email = null;

    #[ODM\Field]
    private ?string $password = null;

    #[ODM\Field(type: "string", nullable: true)]
    private ?string $resetToken = null;

    #[ODM\Field(type: "date", nullable: true)]
    private ?\DateTime $resetTokenExpiresAt = null;

    #[ODM\Field(type: "collection")]
    private array $roles = [];

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    // Cours créés par ce professeur
    #[ODM\ReferenceMany(targetDocument: Classroom::class, nullable: true, mappedBy: 'teacher')]
    private Collection $classrooms;

    // AJOUT : Cours où cet utilisateur est étudiant
    #[ODM\ReferenceMany(targetDocument: Classroom::class, storeAs: "id")]
    private Collection $enrolledClassrooms;

    #[ODM\ReferenceMany(targetDocument: Assignment::class, mappedBy: 'assignedTo')]
    private Collection $assignments;

    #[ODM\ReferenceMany(targetDocument: DutyRendered::class, nullable: true, mappedBy: 'student')]
    private Collection $dutysRendered;

    public function __construct()
    {
        $this->enrolledClassrooms = new ArrayCollection(); // AJOUT
        $this->dutysRendered = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->classrooms = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // CHANGEMENT : Gestion des rôles compatible Symfony Security
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir qu'au moins ROLE_USER est présent
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    // Méthodes de commodité pour les rôles
    public function isTeacher(): bool
    {
        return $this->hasRole('ROLE_TEACHER');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('ROLE_STUDENT');
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

    // Cours créés (pour les professeurs)
    /**
     * @return Collection<int, Classroom>
     */
    public function getClassrooms(): Collection
    {
        return $this->classrooms;
    }

    public function addClassroom(Classroom $classroom): static
    {
        if (!$this->classrooms->contains($classroom)) {
            $this->classrooms->add($classroom);
            $classroom->setTeacher($this);
        }
        return $this;
    }

    public function removeClassroom(Classroom $classroom): static
    {
        if ($this->classrooms->removeElement($classroom)) {
            if ($classroom->getTeacher() === $this) {
                $classroom->setTeacher(null);
            }
        }
        return $this;
    }

    // AJOUT : Cours où l'utilisateur est inscrit (pour les étudiants)
    /**
     * @return Collection<int, Classroom>
     */
    public function getEnrolledClassrooms(): Collection
    {
        return $this->enrolledClassrooms;
    }

    public function addEnrolledClassroom(Classroom $classroom): static
    {
        if (!$this->enrolledClassrooms->contains($classroom)) {
            $this->enrolledClassrooms->add($classroom);
            $classroom->addStudent($this); // synchro côté Course
        }
        return $this;
    }

    public function removeEnrolledClassroom(Classroom $classroom): static
    {
        if ($this->enrolledClassrooms->removeElement($classroom)) {
            $classroom->removeStudent($this); // synchro côté Course
        }
        return $this;
    }


    public function isEnrolledIn(Classroom $classroom): bool
    {
        return $this->enrolledClassrooms->contains($classroom);
    }

    // Gestion des assignments
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->addAssignedTo($this);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            $assignment->removeAssignedTo($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, DutyRendered>
     */
    public function getDutysRendered(): Collection
    {
        return $this->dutysRendered;
    }

    public function addDutyRendered(DutyRendered $dutyRendered): static
    {
        if (!$this->dutysRendered->contains($dutyRendered)) {
            $this->dutysRendered->add($dutyRendered);
            $dutyRendered->setStudent($this);
        }
        return $this;
    }

    public function removeDutyRendered(DutyRendered $dutyRendered): static
    {
        if ($this->dutysRendered->removeElement($dutyRendered)) {
            if ($dutyRendered->getStudent() === $this) {
                $dutyRendered->setStudent(null);
            }
        }
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTime
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTime $expiresAt): self
    {
        $this->resetTokenExpiresAt = $expiresAt;
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

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Laisse vide sauf si tu veux supprimer temporairement des données sensibles
    }
}