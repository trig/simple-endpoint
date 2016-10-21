<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EndpointControllerTest extends WebTestCase
{
    public function testLog()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/log');
    }

}
