<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClassroomControllerTest extends WebTestCase
{
    private $client;
    private $tokenTeacher;
    private $tokenStudent;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Méthode helper pour récupérer un token d'authentification
     */
    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password
            ])
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        return $response['token'] ?? throw new \Exception("Failed to get auth token for {$email}");
    }

    private function authHeaders(string $token): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ];
    }

    public function testCreateClassroomSuccess(): void
    {
        $token = $this->getAuthToken('formateur@test.com', 'password123');

        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            $this->authHeaders($token),
            json_encode([
                'title' => 'Mathématique',
                'description' => 'Cours de maths niveau lycée'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('classroom', $responseData);
        $this->assertEquals('Mathématique', $responseData['classroom']['title']);
        $this->assertEquals('Cours de maths niveau lycée', $responseData['classroom']['description']);
        $this->assertNotEmpty($responseData['classroom']['id']);
        $this->assertNotEmpty($responseData['classroom']['code']);
    }

    public function testCreateClassroomIsExisting(): void
    {
        $token = $this->getAuthToken('formateur@test.com', 'password123');

        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            $this->authHeaders($token),
            json_encode([
                'title' => 'Mathématique',
                'description' => 'Cours de maths niveau lycée'
            ])
        );

        $this->assertResponseStatusCodeSame(409);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            'Classroom already existing',
            $responseData['error']
        );
    }

    public function testCreateClassroomUnauthorized(): void
    {
        $token = $this->getAuthToken('étudiant@test.com', 'password123');

        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            $this->authHeaders($token), // Utiliser le token étudiant
            json_encode([
                'title' => 'Français',
                'description' => 'Cours de français niveau lycée'
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Unauthorized", $responseData['error']);
    }

    public function testCreateClassroomWithInvalidData(): void
    {
        $token = $this->getAuthToken('formateur@test.com', 'password123');

        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            $this->authHeaders($token),
            json_encode([]) // Données vides
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Title is require !", $responseData['error']);
    }

    // Tests pour l'update de classroom    
    private function createTestClassroom(string $teacherToken, string $title): array
    {
        $this->client->request(
            'POST',
            '/api/classroom',
            [],
            [],
            $this->authHeaders($teacherToken),
            json_encode([
                'title' => $title,
                'description' => 'Description initiale'
            ])
        );

        // Vérifier que la création a réussi
        $this->assertResponseStatusCodeSame(
            201,
            'Failed to create test classroom: ' . $this->client->getResponse()->getContent()
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        if (!isset($response['classroom'])) {
            throw new \Exception('Failed to create test classroom: ' . json_encode($response));
        }

        return $response['classroom'];
    }

    public function testUpdateClassroomSuccess(): void
    {
        $teacherToken = $this->getAuthToken('formateur@test.com', 'password123');

        // Créer un classroom d'abord
        $classroom = $this->createTestClassroom($teacherToken, 'mon classroom');

        // Mettre à jour le classroom
        $this->client->request(
            'PUT',
            '/api/classrooms/' . $classroom['id'],
            [],
            [],
            $this->authHeaders($teacherToken),
            json_encode([
                'title' => 'Nouveau Titre',
                'description' => 'Nouvelle description'
            ])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Classroom mis à jour avec succès', $responseData['message']);
        $this->assertArrayHasKey('classroom', $responseData);
        $this->assertEquals('Nouveau Titre', $responseData['classroom']['title']);
        $this->assertEquals('Nouvelle description', $responseData['classroom']['description']);
        $this->assertEquals($classroom['id'], $responseData['classroom']['id']);
        $this->assertEquals($classroom['code'], $responseData['classroom']['code']);
    }

    public function testUpdateClassroomOnlyTitle(): void
    {
        $teacherToken = $this->getAuthToken('formateur@test.com', 'password123');

        // Créer un classroom d'abord
        $classroom = $this->createTestClassroom($teacherToken, 'maths');

        // Mettre à jour seulement le titre
        $this->client->request(
            'PUT',
            '/api/classrooms/' . $classroom['id'],
            [],
            [],
            $this->authHeaders($teacherToken),
            json_encode([
                'title' => 'Titre modifié seulement'
            ])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Classroom mis à jour avec succès', $responseData['message']);
        $this->assertEquals('Titre modifié seulement', $responseData['classroom']['title']);
        $this->assertEquals('Description initiale', $responseData['classroom']['description']); // Description inchangée
    }

    public function testUpdateClassroomOnlyDescription(): void
    {
        $teacherToken = $this->getAuthToken('formateur@test.com', 'password123');

        // Créer un classroom d'abord
        $classroom = $this->createTestClassroom($teacherToken, 'Techno');

        // Mettre à jour seulement la description
        $this->client->request(
            'PUT',
            '/api/classrooms/' . $classroom['id'],
            [],
            [],
            $this->authHeaders($teacherToken),
            json_encode([
                'description' => 'Description modifiée seulement'
            ])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Classroom mis à jour avec succès', $responseData['message']);
        $this->assertEquals('Techno', $responseData['classroom']['title']); // Titre inchangé
        $this->assertEquals('Description modifiée seulement', $responseData['classroom']['description']);
    }

    public function testUpdateClassroomUnAuthorize(): void
    {
        $teacherToken = $this->getAuthToken('formateur@test.com', 'password123');
        $studentToken = $this->getAuthToken('étudiant@test.com', 'password123');

        // Créer un classroom d'abord
        $classroom = $this->createTestClassroom($teacherToken, 'Eps');

        $this->client->request(
            'PUT',
            '/api/classrooms/' . $classroom['id'],
            [],
            [],
            $this->authHeaders($studentToken),
            json_encode([
                'title' => 'Title modifiée',
                'description' => 'Description modifié'
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(
            'Unauthorized',
            $responseData['error']
        );
    }
}