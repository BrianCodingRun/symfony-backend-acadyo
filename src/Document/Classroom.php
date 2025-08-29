<?php

namespace App\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\CourseController;
use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

#[ODM\Document(repositoryClass: ClassroomRepository::class)]
#[ODM\HasLifecycleCallbacks]
#[ApiResource(operations: [
    new Get(),
    new GetCollection(),
    new Post(
        security: "is_granted('ROLE_TEACHER')",
        name: 'create_course',
        controller: CourseController::class
    ),
    new Put(
        security: "is_granted('ROLE_TEACHER')",
        name: 'update_course',
        controller: CourseController::class
    ),
    new Delete(security: "is_granted('ROLE_TEACHER')")
])]
class Classroom
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field]
    private ?string $title = null;

    #[ODM\Field(nullable: true)]
    private ?string $description = null;

    #[ODM\Field]
    private ?string $code = null;

    #[ODM\ReferenceOne(targetDocument: User::class, inversedBy: 'classrooms')]
    private ?User $teacher = null;

    #[ODM\ReferenceMany(targetDocument: User::class, storeAs: "id")]
    private Collection $students;

    #[ODM\Field]
    private ?\DateTimeImmutable $createdAt = null;

    #[ODM\Field]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ODM\ReferenceMany(targetDocument: Course::class, mappedBy: 'classroom')]
    private Collection $courses;

    #[ODM\ReferenceMany(targetDocument: Assignment::class, mappedBy: 'classroom')]
    private Collection $assignments;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->students = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->addEnrolledClassroom($this); // synchro côté User
        }
        return $this;
    }

    public function removeStudent(User $student): static
    {
        if ($this->students->removeElement($student)) {
            $student->removeEnrolledClassroom($this); // synchro côté User
        }
        return $this;
    }


    public function hasStudent(User $student): bool
    {
        return $this->students->contains($student);
    }

    public function getStudentsCount(): int
    {
        return $this->students->count();
    }

    // Pour l'API - retourne un array d'IDs des étudiants
    public function getStudentIds(): array
    {
        return $this->students->map(function (User $student) {
            return $student->getId();
        })->toArray();
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
            $course->setClassroom($this);
        }
        return $this;
    }

    public function removeCourse(Course $course): static
    {
        if ($this->courses->removeElement($course)) {
            if ($course->getClassroom() === $this) {
                $course->setClassroom(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setClassroom($this);
        }
        return $this;
    }

    public function removeAssignment(Assignment $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getClassroom() === $this) {
                $assignment->setClassroom(null);
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

    #[ODM\PreRemove]
    public function onPreRemove(LifecycleEventArgs $args): void
    {
        $dm = $args->getDocumentManager();

        foreach ($this->getStudents() as $student) {
            $student->removeEnrolledCourse($this);
            $dm->persist($student);
        }

        // On flush dans le listener pour garantir la synchro
        $dm->flush();
    }
}
