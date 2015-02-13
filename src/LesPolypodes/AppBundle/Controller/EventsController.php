<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use LesPolypodes\AppBundle\Entity\FormCal;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use LesPolypodes\AppBundle\Services\CalDAVClientProvider;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * Class EventsController
 * @package LesPolypodes\AppBundle\Controller
 */
class EventsController extends Controller
{

    /**
     * @var CalDAVClientProvider
     */
    protected $calDAVClientProvider;


    /**
     * Send a VCalendar object to the server.
     *
     * @param string    $serverName
     * @param string    $calendarName
     * @param VCalendar $vCal
     *
     * @return string                                                  raw ics-formatted vCal
     * @throws \Exception
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    protected function persistEvent($serverName, $calendarName, $vCal)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);

        $rawCal = $vCal->serialize();

        if ($calDavClient->create($rawCal) == null)
            throw new \Exception(sprintf("Can't persist the event named '%s'.",
                $vCal->VEVENT->SUMMARY));

        return $rawCal;
    }


    /**
     * Get a SimpleCalDAVClient object.
     * Connect to the server and calendar given.
     *
     * @param string $serverName
     * @param string $calendarName
     *
     * @return \LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient
     */
    protected function getSimplecalDavClient($serverName, $calendarName = '')
    {
        $this->calDAVClientProvider = $this->container->get('calDAVClientProvider');

        $calDAVClient = $this->calDAVClientProvider->getClient($serverName);
        if (!empty($calendarName)) {
            $calDAVClient->setCalendarByName($calendarName);
        }

        return $calDAVClient;
    }


    /**
     * Display all calendars on a server with multiples options.
     *
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function indexAction($serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName);

        //set to true for debugging.
        $calDavClient->getClient()->dev = true;

        $calendars = $calDavClient->findCalendars();
        $result = array();

        foreach ($calendars as $i=>$calendar) {
            $calDavClient->setCalendar($calendar);
            $events = $calDavClient->getEvents();
            $result[$i] = array(
                "calendar" => $calendar,
                "length" => count($events)
            );
        }

        return $this->render('LesPolypodesAppBundle:Events:index.html.twig', array(
            'result' => $result
        ));
    }


    /**
     * Add an empty calendar to the server.
     *
     * @param $serverName
     * @param $calendarName
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction($serverName, $calendarName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName);

        //set to true for debugging.
        $calDavClient->getClient()->dev = true;

        // $calendarDescription = sprintf('%s\'s calendar', $calDavClient->getClient()->getPrincipalDisplayName());
        $calendarDescription = 'Description';


        $calDavClient->makeCal($calendarName, $calendarDescription);
        

        return $this->redirect($this->generateUrl('les_polypodes_app_index', array(
            'serverName' => $serverName,
        )));
    }


    /**
     * Remove a given calendar from the server.
     *
     * @param $serverName
     * @param $calendarName
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeAction($serverName, $calendarName)
    {
        
        $calDavClient = $this->getSimplecalDavClient($serverName);
        $calendars = $calDavClient->findCalendars();

        $calDavClient->deleteCal($calendarName);

        return $this->redirect($this->generateUrl('les_polypodes_app_index', array(
            'serverName' => $serverName,
        )));
    }


    /**
     * Display all events in a calendar, with multiples options.
     *
     * @param $serverName
     * @param $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function eventAction($serverName, $calendarName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        $reader = new Reader();

        $dataContainer = new \stdClass();
        $dataContainer->vcal = null;
        $dataContainer->dateStart = null;
        $dataContainer->dateEnd = null;

        $datas = [];
        foreach ($events as $event) {
            $vcal = $reader->read($event->getData());
            $dataContainer->vcal = $vcal;
            $dataContainer->dateStart = (new \datetime($vcal->VEVENT->DTSTART))->format('Y-m-d H:i');
            $dataContainer->dateEnd = (new \datetime($vcal->VEVENT->DTEND))->format('Y-m-d H:i');

            $datas[] = clone $dataContainer;
        }

        return $this->render('LesPolypodesAppBundle:Events:event.html.twig', array(
            'calendarName' => $calendarName,
            'datas' => $datas,
        ));
    }


    /**
     * Display all events in a calendar, in a rawish version.
     *
     * @param $serverName
     * @param $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function eventRawAction($serverName, $calendarName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        return $this->render('LesPolypodesAppBundle:Events:eventRaw.html.twig', array(
            'calendarName' => $calendarName,
            'events' => $events,
        ));
    }


    /**
     * Create a fake vcal, send it to the server, and display the result.
     *
     * @param $serverName
     * @param $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function createAction($serverName, $calendarName)
    {
        // TODO: Display it in a pretty format too.
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);

        $vCalProvider = $this->container->get('vCalProvider');
        $vCal = $vCalProvider->createFakeVCal();

        $this->persistEvent($serverName, $calendarName, $vCal);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vCal' => $vCal->serialize(),
            'calendarName' => $calendarName,
        ));
    }


    /**
     * Create and send 'n' faked events to the server, then display all the events.
     *
     * @param $serverName
     * @param $calendarName
     * @param $n
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function insertAction($serverName, $calendarName, $n)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);

        
        for ($i = 0; $i < $n; $i++) {
            $this->persistEvent(
                $serverName,
                $calendarName,
                $this->container->get('vCalProvider')->createFakeVCal());
        }

        return $this->forward('LesPolypodesAppBundle:Events:event', array(
                'calendarName' => $calendarName,
                'serverName' => $serverName,
            ));
    }


    /**
     * Create and send 'n' faked events to the server, but in one compressed .ics, then display all the events.
     *
     * @param $serverName
     * @param $calendarName
     * @param $n
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function insertCmpAction($serverName, $calendarName, $n)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        
    // Not Working. Send 400 html code when VCAL contains multiple VEVENT with differents UID
        $vCal = new VCalendar();
        $vCal->PRODID = '-//ODE Dev//Faker//FR';

        for ($i = 0; $i < $n; $i++) {
            $vCal->add($this->container->get('vCalProvider')->createFakeVCal()->VEVENT);
        }

        $this->persistEvent($serverName, $calendarName, $vCal);

        return $this->forward('LesPolypodesAppBundle:Events:event', array(
                'calendarName' => $calendarName,
                'serverName' => $serverName,
            ));
    }


    /**
     * Display a form that creates an event, and send it to the server.
     *
     * @param Request $request
     * @param         $serverName
     * @param         $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function formAction(Request $request, $serverName, $calendarName)
    {
        $event = new FormCal();
        // Valeurs par défaut
        $event->setName("Nom de l'évènement");
        $event->setStartDate(new \DateTime());
        $event->setEndDate((new \DateTime())->add(new \DateInterval('PT1H')));
        $event->setLocation("Adresse de l'évènement");
        $event->setDescription('Décrivez votre évènement');
        $event->setPrice('0');
        $event->setOrganizer('organisateur@exemple.com');

        $form = $this->createFormBuilder($event)
            ->add('name', 'text')
            ->add('startDate', 'datetime')
            ->add('endDate', 'datetime')
            ->add('location', 'text')
            ->add('description', 'textarea')
            ->add('price', 'money')
            ->add('organizer','email')
            ->add('Valider', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {

            $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
            $vCalProvider = $this->container->get('vCalProvider');
            $vCal = $vCalProvider->createVCal($event);

            $this->persistEvent($serverName, $calendarName, $vCal);

            return $this->redirect($this->generateUrl('les_polypodes_app_event', array(
                'calendarName' => $calendarName,
                'serverName' => $serverName,
            )));
        }

        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView(),
            'calendarName' => $calendarName,
            ));
    }


    /**
     * Display a form that searchs events by time, name, or other parameters then display
     * them in pretty and rawish way, with multiples options.
     *
     * @param $serverName
     * @param $calendarName
     * @param $n
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function selectAction ($serverName, $calendarName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        // TODO: A form that searchs events

        $sorting = new FormCal();
        $sorting->setStartDate(new \DateTime());
        $sorting->setEndDate((new \DateTime())->add(new \DateInterval('PT1H')));

        $form = $this->createFormBuilder($sorting)
            ->add('startDate', 'datetime')
            ->add('endDate', 'datetime')
            ->add('Valider', 'submit')
            ->getForm();

        return $this->render('LesPolypodesAppBundle:Events:select.html.twig', array(
            'form' => $form->createView(),
            'calendarName' => $calendarName,
        ));
    }


    /**
     * Delete all events in a calendar.
     *
     * @param $serverName
     * @param $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function clearAction($serverName, $calendarName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);

        $events = $calDavClient->getEvents();
        while (!empty($events)) {
            $calDavClient->delete($events[0]->getHref(), $events[0]->getEtag());
            $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
            $events = $calDavClient->getEvents();
        }

        $this->get('session')->getFlashBag()->add(
            'notice',
            'The calendar '.$calendarName.' has been cleared !');

        return $this->redirect($this->generateUrl('les_polypodes_app_index', array(
            'serverName' => $serverName,
            )));
    }


    /**
     * Delete a calendar and create it back to empty it.
     *
     * @param $serverName
     * @param $calendarName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function clearHardAction($serverName, $calendarName)
    {
        // TODO: Delete a calendar, save his name, and create one with the same name, then show all calendars.
        
        $this->get('session')->getFlashBag()->add(
            'notice',
            'Not implemented yet');

        return $this->redirect($this->generateUrl('les_polypodes_app_index', array(
            'serverName' => $serverName,
        )));
    }


    /**
     * Display one given event, in pretty and rawish way, with multiples options.
     *
     * @param $serverName
     * @param $calendarName
     * @param $eventID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function viewAction ($serverName, $calendarName, $eventID)
    {
        // TODO: Select one event by UID, display it.

        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        $reader = new Reader();

        $dataContainer = new \stdClass();
        $dataContainer->vcal = null;
        $dataContainer->dateStart = null;
        $dataContainer->dateEnd = null;

        foreach ($events as $event){
            $vCal = $reader->read($event->getData());
            if ($vCal->VEVENT->UID == $eventID) {
                break;
            }
        }

        $dataContainer->vcal = $vCal;
        $dataContainer->dateStart = (new \datetime($vCal->VEVENT->DTSTART))->format('Y-m-d H:i');
        $dataContainer->dateEnd = (new \datetime($vCal->VEVENT->DTEND))->format('Y-m-d H:i');
        
        return $this->render('LesPolypodesAppBundle:Events:view.html.twig', array(
            'calendarName' => $calendarName,
            'data' => $dataContainer,
        ));
    }


    /**
     * Delete an Event, then display all the events.
     *
     * @param $serverName
     * @param $calendarName
     * @param $eventID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function deleteAction($serverName, $calendarName, $eventID)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        $reader = new Reader();

        foreach ($events as $event) {

            $vCal = $reader->read($event->getData());
            if ($vCal->VEVENT->UID == $eventID) {
                break;
            }
        }

        $calDavClient->delete($event->getHref(), $event->getEtag());
        $datas = [];

        $this->get('session')->getFlashBag()->add(
            'notice',
            'The event "'.$vCal->VEVENT->SUMMARY.'" has been deleted !');

        return $this->redirect($this->generateUrl('les_polypodes_app_event', array(
            'calendarName' => $calendarName,
            'serverName' => $serverName,
            )));
     }


    /**
     * Display a form version of an event, and send modifications to the server
     *
     * @param $serverName
     * @param $calendarName
     * @param $eventID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($serverName, $calendarName, $eventID)
    {
        // TODO: update 1 event
        // TODO: all events between 2 datetimes
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:update.html.twig', array(
            // 'form' => $form->createView(),
            'calendarName' => $calendarName,
        ));
    }
}
