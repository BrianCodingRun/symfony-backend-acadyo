<?php

namespace App\Tests\Controller;

use App\Document\Classroom;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Course;
use App\Document\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseControllerTest extends WebTestCase
{
    private $client;
    private $documentManager;
    private $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->documentManager = static::getContainer()->get(DocumentManager::class);

        // Nettoyage des collections
        $this->documentManager->createQueryBuilder(Course::class)->remove()->getQuery()->execute();
        $this->documentManager->createQueryBuilder(User::class)->remove()->getQuery()->execute();
        $this->documentManager->createQueryBuilder(Classroom::class)->remove()->getQuery()->execute();

        // Création utilisateur test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(["ROLE_TEACHER"]);

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $hasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);

        $this->documentManager->persist($user);
        $this->documentManager->flush();

        // Authentification pour récupérer le token JWT
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $response['token'] ?? null;
    }

    private function authHeaders(): array
    {
        return [
            'HTTP_Authorization' => 'Bearer ' . $this->token,
        ];
    }

    private function createTestFile(string $content = 'test content', string $name = 'test.txt'): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, $content);

        return new UploadedFile($tempFile, $name, null, null, true);
    }

    // ============ TESTS CRÉATION DE COURS (SANS MOCK) ============

    public function testCreateCourseWithoutFile(): void
    {
        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Cours sans fichier',
                'content' => 'Contenu du cours',
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Cours sans fichier', $responseData['title']);
        $this->assertEquals('Contenu du cours', $responseData['content']);
        $this->assertNull($responseData['filePath']);
    }

    public function testCreateCourseUntitled(): void
    {
        $this->client->request(
            'POST',
            '/api/courses',
            [],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Le titre est obligatoire", $responseData['error']);
    }

    public function testCreateCourseWithEmptyTitle(): void
    {
        $this->client->request(
            'POST',
            '/api/courses',
            ['title' => ''],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateCourseWithFakeClassroom(): void
    {
        $fakeClassroom = '68908ce91a3d47337a003b60';

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Mon support de cours',
                'classroom' => $fakeClassroom
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Classroom introuvable", $responseData['error']);
    }

    public function testCreateCourseWithValidClassroom(): void
    {
        // Créer un classroom d'abord
        $classroom = new Classroom();
        $classroom->setTitle("Symfony Avancé");
        $this->documentManager->persist($classroom);
        $this->documentManager->flush();

        $classroomId = $classroom->getId();

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Mon support de cours',
                'content' => 'Contenu détaillé',
                'classroom' => $classroomId
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString($classroomId, $responseData['classroom']);
    }

    public function testCreateCourseWithClassroomApiFormat(): void
    {
        // Créer un classroom
        $classroom = new Classroom();
        $classroom->setTitle("Test Classroom");
        $this->documentManager->persist($classroom);
        $this->documentManager->flush();

        // Utiliser le format API /api/classrooms/ID
        $classroomApi = '/api/classrooms/' . $classroom->getId();

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Cours avec classroom API format',
                'classroom' => $classroomApi
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // ============ TESTS MISE À JOUR DE COURS ============

    public function testUpdateCourseSuccess(): void
    {
        $course = new Course();
        $course->setTitle('Ancien titre');
        $course->setContent('Ancien contenu');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $id = $course->getId();

        $this->client->request(
            'POST',
            "/api/courses/$id",
            [
                'title' => 'Titre modifié',
                'content' => 'Contenu modifié',
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Titre modifié', $responseData['title']);
        $this->assertEquals('Contenu modifié', $responseData['content']);
    }

    public function testUpdateCourseNotFound(): void
    {
        $fakeId = '64e8b7c2f1c2a00000000000';

        $this->client->request(
            'POST',
            "/api/courses/$fakeId",
            ['title' => 'Nouveau titre'],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('introuvable', $responseData['error']);
    }

    public function testUpdateCoursePartialUpdate(): void
    {
        $course = new Course();
        $course->setTitle('Titre original');
        $course->setContent('Contenu original');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        // Mise à jour partielle - seulement le titre
        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            ['title' => 'Nouveau titre seulement'],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Nouveau titre seulement', $responseData['title']);
        $this->assertEquals('Contenu original', $responseData['content']); // Inchangé
    }

    public function testUpdateCourseRemoveClassroom(): void
    {
        // Créer un classroom et un cours associé
        $classroom = new Classroom();
        $classroom->setTitle("Classroom à supprimer");
        $this->documentManager->persist($classroom);

        $course = new Course();
        $course->setTitle('Cours avec classroom');
        $course->setClassroom($classroom);
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        // Supprimer l'association en passant une chaîne vide
        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            [
                'title' => 'Cours sans classroom',
                'classroom' => '' // Chaîne vide pour supprimer l'association
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNull($responseData['classroom']);
    }

    public function testUpdateCourseWithInvalidClassroom(): void
    {
        $course = new Course();
        $course->setTitle('Cours test');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            [
                'title' => 'Test',
                'classroom' => 'invalid-classroom-id'
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('introuvable', $responseData['error']);
    }

    // ============ TESTS SUPPRESSION DE FICHIER ============

    public function testDeleteCourseFileNotFound(): void
    {
        $fakeId = '64e8b7c2f1c2a00000000000';

        $this->client->request(
            'DELETE',
            "/api/courses/$fakeId/file",
            [],
            [],
            $this->authHeaders()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('introuvable', $responseData['error']);
    }

    public function testDeleteCourseFileWithoutFile(): void
    {
        $course = new Course();
        $course->setTitle('Cours sans fichier');
        $course->setContent('Description du cours');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $id = $course->getId();

        $this->client->request(
            'DELETE',
            "/api/courses/$id/file",
            [],
            [],
            $this->authHeaders()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('supprimé', $responseData['message']);
    }

    // ============ TESTS D'AUTHENTIFICATION ============

    public function testCreateCourseWithoutAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/courses',
            ['title' => 'Test sans auth'],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateCourseWithoutAuth(): void
    {
        $course = new Course();
        $course->setTitle('Test');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            ['title' => 'Modifié'],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteCourseFileWithoutAuth(): void
    {
        $course = new Course();
        $course->setTitle('Test');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $this->client->request('DELETE', "/api/courses/{$course->getId()}/file");

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ============ TESTS AVEC FICHIERS RÉELS (optionnels) ============

    /**
     * @group upload
     */
    public function testCreateCourseWithRealFile(): void
    {
        // Créer un fichier de test
        $testFile = $this->createTestFile('Contenu du fichier test', 'test-file.txt');

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Cours avec fichier réel',
                'content' => 'Description détaillée',
            ],
            [
                'file' => $testFile
            ],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Cours avec fichier réel', $responseData['title']);
        $this->assertNotNull($responseData['filePath']);

        // Vérifier que c'est bien une URL Cloudinary
        if ($responseData['filePath']) {
            $this->assertStringContainsString('cloudinary', $responseData['filePath']);
        }
    }

    /**
     * @group upload
     */
    public function testUpdateCourseWithRealFile(): void
    {
        // Créer un cours d'abord
        $course = new Course();
        $course->setTitle('Cours à modifier');
        $course->setContent('Contenu original');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        // Créer un fichier de test
        $testFile = $this->createTestFile('Nouveau fichier', 'nouveau-fichier.txt');

        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            [
                'title' => 'Cours modifié avec fichier',
                'content' => 'Contenu modifié',
            ],
            [
                'file' => $testFile
            ],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Cours modifié avec fichier', $responseData['title']);
        $this->assertNotNull($responseData['filePath']);
    }

    /**
     * @group upload
     */
    public function testDeleteCourseFileWithRealFile(): void
    {
        // Créer un cours avec un fichier réel d'abord
        $course = new Course();
        $course->setTitle('Cours avec fichier à supprimer');
        $course->setContent('Description');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        // Ajouter un fichier via l'API
        $testFile = $this->createTestFile('Fichier à supprimer', 'fichier-a-supprimer.txt');

        $this->client->request(
            'POST',
            "/api/courses/{$course->getId()}",
            [],
            ['file' => $testFile],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Maintenant supprimer le fichier
        $this->client->request(
            'DELETE',
            "/api/courses/{$course->getId()}/file",
            [],
            [],
            $this->authHeaders()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifier que le fichier a été supprimé
        $this->documentManager->clear();
        $updatedCourse = $this->documentManager->getRepository(Course::class)->find($course->getId());

        $this->assertNull($updatedCourse->getFilePath());
        $this->assertNull($updatedCourse->getFilePublicId());
    }
}