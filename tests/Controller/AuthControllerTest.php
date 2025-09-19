<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{

    public function testRequestResetPasswordWithoutEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/request-reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Email manquant', $responseData['error']);
    }

    public function testRequestResetPasswordWithUnknownEmail(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/request-reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'unknown@test.com'])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Si un compte existe, un email sera envoyé.',
            $responseData['message']
        );
    }

    public function testRequestResetPasswordWithValidEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/request-reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'étudiant@test.com'])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Si un compte existe, le lien de réinitialisation de votre mot de passe sera envoyé à cette adresse email.',
            $responseData['message']
        );
    }
    public function testResetPasswordWithEmptyToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => '', 'newPassword' => ''])
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Token ou mot de passe manquant',
            $responseData['error']
        );

    }
    public function testResetPasswordWithInvalidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'invalidtoken', 'newPassword' => 'everè565dv'])
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Token invalide ou expiré',
            $responseData['error']
        );

    }
    public function testResetPasswordWithValidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/reset-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'valid_token', 'newPassword' => '123abc'])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Mot de passe mis à jour avec succès',
            $responseData['message']
        );

    }

    public function testRegisterEmptyField(): void
    {
        $client = static::createClient();

        $client->request(
            "POST",
            '/api/register',
            [],
            [],
            ['CONTENT-TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Missing fields',
            $responseData['error']
        );
    }
    public function testRegisterExistingUser(): void
    {
        $client = static::createClient();
        $name = "user3";
        $email = "étudiant@test.com"; // un utilisateur existe déjà avec cette email en bdd
        $password = "123abc";

        $client->request(
            "POST",
            '/api/register',
            [],
            [],
            ['CONTENT-TYPE' => 'application/json'],
            json_encode(['name' => $name, 'email' => $email, 'password' => $password])
        );

        $this->assertResponseStatusCodeSame(409);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Email already exists',
            $responseData['error']
        );
    }
    public function testRegister(): void
    {
        $client = static::createClient();

        $name = "user1";
        $email = "user1@test.fr";
        $password = "123abc";

        $client->request(
            "POST",
            '/api/register',
            [],
            [],
            ['CONTENT-TYPE' => 'application/json'],
            json_encode(['name' => $name, 'email' => $email, 'password' => $password])
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(
            'Utilisateur enregistré avec succès !',
            $responseData['message']
        );
    }

}
