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

    public function testCreateCourseSuccess(): void
    {
        $file = new UploadedFile(
            __DIR__ . '/fixtures/test-file.txt',
            'test-file.txt'
        );

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Nouveau cours',
                'content' => 'Contenu du cours',
            ],
            [
                'file' => $file
            ],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
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

        $this->assertEquals(
            "Le titre est obligatoire",
            $responseData['error']
        );
    }

    public function testCreateCourseWithClassroomFake(): void
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

        $this->assertEquals(
            "Classroom introuvable",
            $responseData['error']
        );
    }
    public function testCreateCourseWithClassroom(): void
    {
        // Créer un classroom d'abord
        $classroom = new Classroom();
        $classroom->setTitle("Symfony");

        $this->documentManager->persist($classroom);
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            array_merge([
                'CONTENT_TYPE' => 'application/json',
            ], $this->authHeaders()),
            json_encode([
                'title' => 'Symfony',
            ])
        );

        // Récupère l'id du classroom
        $r = json_decode($this->client->getResponse()->getContent(), true);
        $classroom = $r['classroom']['@id'];

        // Création du support de cours
        $course = new Course();
        $course->setTitle('Mon support de cours');
        $course->setClassroom($classroom);

        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            '/api/courses',
            [
                'title' => 'Mon support de cours',
                'classroom' => $classroom
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testGetCourseByIdNotFound(): void
    {
        $this->client->request('GET', '/api/courses/64e8b7c2f1c2a00000000000', [], [], $this->authHeaders());
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateCourseSuccess(): void
    {
        $course = new Course();
        $course->setTitle('Ancien titre');
        $course->setContent('Ancien contenu');
        $course->setFilePath('https://cloudinary.test/test-file.doc');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $id = $course->getId();

        $this->client->request(
            'POST',
            "/api/courses/$id",
            [
                'title' => 'Titre modifié',
                'content' => 'Contenu modifiée',
            ],
            [],
            array_merge([
                'CONTENT_TYPE' => 'multipart/form-data',
            ], $this->authHeaders())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDeleteCourseFileSuccess(): void
    {
        // Créer un cours avec un fichier
        $course = new Course();
        $course->setTitle('Cours avec fichier à supprimer');
        $course->setContent('Description du cours');
        $course->setFilePath('https://cloudinary.test/fake-image.jpg');
        $course->setFilePublicId('fake_public_id_123');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $id = $course->getId();

        // Faire la requête de suppression du fichier
        $this->client->request(
            'DELETE',
            "/api/courses/$id/file",
            [],
            [],
            $this->authHeaders()
        );

        // Vérifier la réponse et afficher debug si échec
        $response = $this->client->getResponse();

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            $this->fail("Status attendu: 200, reçu: " . $response->getStatusCode() .
                ". Contenu: " . $response->getContent());
        }

        // Recharger le cours depuis la base de données
        $this->documentManager->clear();
        $updatedCourse = $this->documentManager->getRepository(Course::class)->find($id);

        // Vérifier les valeurs et afficher debug si problème
        $filePath = $updatedCourse->getFilePath();
        $filePublicId = $updatedCourse->getFilePublicId();

        if ($filePath !== null || $filePublicId !== null) {
            $this->fail("ÉCHEC - FilePath: '" . ($filePath ?? 'NULL') .
                "', FilePublicId: '" . ($filePublicId ?? 'NULL') .
                "'. Response: " . $response->getContent());
        }

        // Si on arrive ici, tout va bien
        $this->assertNull($updatedCourse->getFilePath());
        $this->assertNull($updatedCourse->getFilePublicId());
        $this->assertEquals('Cours avec fichier à supprimer', $updatedCourse->getTitle());
    }

    public function testDeleteCourseFileNotFound(): void
    {
        // Test avec un ID de cours inexistant
        $fakeId = '64e8b7c2f1c2a00000000000';

        $this->client->request(
            'DELETE',
            "/api/courses/$fakeId/file",
            [],
            [],
            $this->authHeaders()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('introuvable', $responseData['error']);
    }

    public function testDeleteCourseFileWithoutFile(): void
    {
        // Créer un cours sans fichier
        $course = new Course();
        $course->setTitle('Cours sans fichier');
        $course->setContent('Description du cours');
        $this->documentManager->persist($course);
        $this->documentManager->flush();

        $id = $course->getId();

        // Faire la requête de suppression du fichier
        $this->client->request(
            'DELETE',
            "/api/courses/$id/file",
            [],
            [],
            $this->authHeaders()
        );

        // La requête devrait réussir même s'il n'y a pas de fichier
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('supprimé', $responseData['message']);
    }
}