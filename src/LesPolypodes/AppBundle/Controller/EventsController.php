<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EventsController extends Controller
{
    public function eventsAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:list.html.twig');
    }
}
