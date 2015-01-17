<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sabre\VObject;
use Faker;

class EventsController extends Controller
{
    public function listAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:list.html.twig');
    }

    public function createAction()
    {
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');
        // see http://sabre.io/vobject/usage/
        $vcard = new VObject\Component\VCard([
            'FN'  => $faker->name,
            'TEL' => $faker->phoneNumber,
        ]);
        $vcard->add('TEL', $faker->phoneNumber, ['type' => 'fax']);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vcard' => $vcard->serialize()
        ));
    }

    public function indexAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:index.html.twig');
    }

    public function readAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:read.html.twig');
    }

    public function updateAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:update.html.twig');
    }

    public function deleteAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:delete.html.twig');
    }
}
