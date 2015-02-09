<?php

namespace LesPolypodes\AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventsControllerTest extends WebTestCase
{
    public function testIndexConnectedToBaiKal()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/baikal/events');

        $this->assertTrue(1 === $crawler->filter('html:contains("Remote calendars exist!")')->count());
    }

    public function testScdcListEventRawAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/baikal/events');

        $link = $crawler->filter('a[id=raw_2]')->link();
        $crawler = $client->click($link);
        $this->assertTrue(0 < $crawler->filter('html:contains("BEGIN:VCALENDAR")')->count());
    }

    public function testDevInsertAction()
    {
        $this->markTestSkipped("too long!");
        $client = static::createClient();
        $crawler = $client->request('GET', '/baikal/events');
        $link = $crawler->filter('a[id=insert2_1]')->link();
        $crawler = $client->click($link);
        $this->assertTrue(2 <= $crawler->filter('fieldset.event')->count());
    }

    public function testFormAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET','/baikal/events');
        $link = $crawler->filter('a:contains("Form")')->link();
        $crawler = $client->click($link);
        $this->assertEquals(1, $crawler->filter('html:contains("Formulaire")')->count());
    }

    public function testDeleteAction()
    {
        $this->markTestIncomplete("to be implemented!");
        $client = static::createClient();
        $crawler = $client->request('GET','/delete/1');
        $this->assertEquals(1, $crawler->filter('html:contains("Delete")')->count());
    }

    public function testUpdateAction()
    {
        $this->markTestIncomplete("to be implemented!");
        $client = static::createClient();
        $crawler = $client->request('GET','/update/1');
        $this->assertEquals(1, $crawler->filter('html:contains("Update")')->count());
    }

    public function testCreateFakeAction()
    {
        $client = static::createClient();
        $crawler = $client->request('GET','/baikal/events/create');
        $this->assertEquals(1, $crawler->filter('html:contains("Fakely generated vObjects")')->count());
        $this->assertTrue(1 ===  $crawler->filter('html:contains("BEGIN:VCALENDAR")')->count());
        $this->assertTrue(1 ===  $crawler->filter('html:contains("END:VCALENDAR")')->count());
    }
}
