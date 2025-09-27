<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryTest extends WebTestCase
{
    public function testGetCategories(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/categories', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer your-test-jwt-token'
        ]);

        $this->assertResponseIsSuccessful();
    }
}
