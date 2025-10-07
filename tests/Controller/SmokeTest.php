<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApiDocUrlIsSuccessful(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/doc');

        self::assertResponseIsSuccessful();
    }

    public function testApiAccountUrlIsSecure(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginRouteCanConnectAValidUser()
    {
        $client = self::createClient();
        $client->request('POST', 'api/login', [], [], [
            'Content-type' => 'application/json',
        ], json_encode([
            'username' => 'toto@toto.fr',
            'password' => 'toto',
        ], JSON_THROW_ON_ERROR));
        $statusCode = $client->getResponse()->getStatusCode();
        dd($statusCode);

    }
}
