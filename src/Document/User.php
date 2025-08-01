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

    // CHANGEMENT : Support des rôles multiples pour Symfony Security
    #[ODM\Field(type: "collection")]
    private array $roles = [];

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    // Cours créés par ce professeur
    #[ODM\ReferenceMany(targetDocument: Course::class, nullable: true, mappedBy: 'teacher')]
    private Collection $courses;

    // AJOUT : Cours où cet utilisateur est étudiant
    #[ODM\ReferenceMany(targetDocument: Course::class, storeAs: "id")]
    private Collection $enrolledCourses;

    #[ODM\ReferenceMany(targetDocument: Assignment::class, mappedBy: 'assignedTo')]
    private Collection $assignments;

    #[ODM\ReferenceMany(targetDocument: Submission::class, nullable: true, mappedBy: 'student')]
    private Collection $submissions;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->enrolledCourses = new ArrayCollection(); // AJOUT
        $this->submissions = new ArrayCollection();
        $this->assignments = new ArrayCollection();
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
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setTeacher($this);
        }
        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            if ($course->getTeacher() === $this) {
                $course->setTeacher(null);
            }
        }
        return $this;
    }

    // AJOUT : Cours où l'utilisateur est inscrit (pour les étudiants)
    /**
     * @return Collection<int, Course>
     */
    public function getEnrolledCourses(): Collection
    {
        return $this->enrolledCourses;
    }

    public function addEnrolledCourse(Course $course): static
    {
        if (!$this->enrolledCourses->contains($course)) {
            $this->enrolledCourses->add($course);
        }
        return $this;
    }

    public function removeEnrolledCourse(Course $course): static
    {
        $this->enrolledCourses->removeElement($course);
        return $this;
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrolledCourses->contains($course);
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
            $submission->setStudent($this);
        }
        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            if ($submission->getStudent() === $this) {
                $submission->setStudent(null);
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

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Laisse vide sauf si tu veux supprimer temporairement des données sensibles
    }
}