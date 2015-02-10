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
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function scdcListAction($serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName);
        $calendars = $calDavClient->findCalendars();

        $result = array();

        foreach ($calendars as $i=>$calendar) {
            $calDavClient = $this->getSimplecalDavClient($serverName, $calendar->getDisplayName());
            $events = $calDavClient->getEvents();
            $result[$i] = array(
                "calendar" => $calendar,
                "length" => count($events)
            );
        }

        return $this->render('LesPolypodesAppBundle:Events:scdcList.html.twig', array(
            'result' => $result
        ));
    }


    /**
     * @param $calendarName
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function scdcListEventAction($calendarName, $serverName)
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

        return $this->render('LesPolypodesAppBundle:Events:scdcListEvent.html.twig', array(
            'calendarName' => $calendarName,
            'datas' => $datas,
        ));
    }

    /**
     * @param $calendarName
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function scdcListEventRawAction($calendarName, $serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        return $this->render('LesPolypodesAppBundle:Events:scdcListEventRaw.html.twig', array(
            'events' => $events,
        ));
    }

    /**
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function createAction($serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName);

        $vCalProvider = $this->container->get('vCalProvider');
        $vCal = $vCalProvider->createFakeVCal();

        $this->persistEvent($serverName, $this->calDAVClientProvider->getCaldavMainCalName(), $vCal);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vCal' => $vCal->serialize(),
            'calendarName' => $this->calDAVClientProvider->getCaldavMainCalName()
        ));
    }

    /**
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($serverName)
    {
        return $this->render('LesPolypodesAppBundle:Events:index.html.twig');
    }

    /**
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($serverName)
    {
        // TODO: update 1 event
        // TODO: all events between 2 datetimes
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:update.html.twig');
    }

    /**
     * @param $calendarName
     * @param $id
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function deleteAction($calendarName, $id, $serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
        $events = $calDavClient->getEvents();

        $reader = new Reader();

        foreach ($events as $event) {

            $vCal = $reader->read($event->getData());
            if ($vCal->VEVENT->UID == $id) {
                break;
            }
        }

        $calDavClient->delete($event->getHref(), $event->getEtag());
        $datas = [];

        return $this->render('LesPolypodesAppBundle:Events:delete.html.twig', array(
            'calendarName' => $calendarName,
            'datas' => $datas,
            ));
    }

    /**
     * @param $calendarName
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \LesPolypodes\AppBundle\Services\CalDAV\CalDAVException
     */
    public function deleteAllAction($calendarName, $serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);

        $events = $calDavClient->getEvents();
        while (!empty($events)) {
            $calDavClient->delete($events[0]->getHref(), $events[0]->getEtag());
            $calDavClient = $this->getSimplecalDavClient($serverName, $calendarName);
            $events = $calDavClient->getEvents();
        }

        return $this->render('LesPolypodesAppBundle:Events:deleteAll.html.twig', array(
            'calendarName' => $calendarName,
        ));
    }

    /**
     * @param Request $request
     * @param         $serverName
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function formAction(Request $request, $serverName)
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

            $calDavClient = $this->getSimplecalDavClient($serverName);
            $vCalProvider = $this->container->get('vCalProvider');
            $vCal = $vCalProvider->createVCal($event);

            $this->persistEvent($serverName, $this->calDAVClientProvider->getCaldavMainCalName(), $vCal);

            return $this->redirect($this->generateUrl('les_polypodes_app_list_event_raw', array(
                'calendarName' => $this->calDAVClientProvider->getCaldavMainCalName(),
                'serverName' => $serverName,
            )));
        }

        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView(),
            ));
    }

    /**
     * @param $calendarName
     * @param $n
     * @param $type
     * @param $serverName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function devInsertAction($calendarName, $n, $type, $serverName)
    {
        $calDavClient = $this->getSimplecalDavClient($serverName);

        switch ($type) {
            case 'standard' :
                for ($i = 0; $i < $n; $i++) {
                    $this->persistEvent(
                        $serverName,
                        $calendarName,
                        $this->container->get('vCalProvider')->createFakeVCal());
                }
                break;
            case 'compressed' :
            // Not Working. Send 400 html code when VCAL contains multiple VEVENT with differents UID
                $vCal = new VCalendar();
                $vCal->PRODID = '-//ODE Dev//Faker//FR';

                for ($i = 0; $i < $n; $i++) {
                    $vCal->add($this->container->get('vCalProvider')->createFakeVCal()->VEVENT);
                }

                $this->persistEvent($serverName, $calendarName, $vCal);
                break;
        }

        return $this->forward('LesPolypodesAppBundle:Events:scdcListEvent', array(
                'calendarName' => $calendarName,
                'serverName' => $serverName,
            ));
    }
}
