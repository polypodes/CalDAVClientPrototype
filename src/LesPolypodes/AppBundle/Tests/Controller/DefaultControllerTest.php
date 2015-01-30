<?php

namespace LesPolypodes\AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/events');

        $this->assertTrue(1 === $crawler->filter('html:contains("Events")')->count());
    }
}
