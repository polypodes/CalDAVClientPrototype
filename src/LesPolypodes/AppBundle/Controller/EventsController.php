<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\DateTime;
use Sabre\VObject;
use Faker;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;
use Sabre\DAV;
use LesPolypodes\AppBundle\Entity\FormCal;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class EventsController extends Controller
{

    protected $caldav_login = null;
    protected $caldav_password = null;
    protected $caldav_host = null;
    protected $maincal_Name = null;
    protected $scdClient = null;
    protected $sabreClient = null;


    protected function getCalDavConnection()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $this->caldav_login = $this->container->getParameter('caldav_login');
        $this->caldav_password = $this->container->getParameter('caldav_password');
        $this->caldav_host = $this->container->getParameter('caldav_host');
        $this->maincal_Name = $this->container->getParameter('maincal_Name');
    }

    protected function getSimplecalDavClient()
    {
        $this->getCalDavConnection();
        $this->scdClient = new SimpleCalDAVClient;
        $url = sprintf("%s%s", $this->caldav_host, $this->caldav_login);
        $this->scdClient->connect($url, $this->caldav_login, $this->caldav_password);

        return $this->scdClient;
    }

    protected function setCalendarSCDC($name)
    {
        $calendarID = $this->scdClient->findCalendarIDByName($name);

        if ($calendarID == null) {
            throw new \Exception('No calendar found with the name "'.$name.'".');
        }

        $this->scdClient->setCalendar($this->scdClient->findCalendars()[$calendarID]);
    }

    protected function createVCal($event)
    {
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');

        $vcal = new VObject\Component\VCalendar();
        $vcal->PRODID = '-//ODE Dev//Form//FR';

        $vevent = $vcal->add('VEVENT');

        $uid = $faker->numerify('ODE-####-####-####-####');

        // $vevent->add('ORGANIZER', $faker->companyEmail);
        $vevent->add('CREATED', $faker->dateTimeBetween('now', 'now'));
        $vevent->add('UID', $uid);
        $vevent->add('TRANSP', array('OPAQUE', 'TRANSPARENT')[rand(0,1)]);
        $vevent->add('SUMMARY', $event->getName());
        $vevent->add('LOCATION', $event->getLocation());
        $vevent->add('DTSTART', $event->getStartDate());
        $vevent->add('DTEND', $event->getEndDate());
        // $vevent->add('X-ODE-PRICE', sprintf('%d€', $faker->randomFloat(2, 0, 100)));
        $vevent->add('DESCRIPTION', $event->getDescription());

        return $vcal;
    }

    protected function createFakeVCal()
    {
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');

        $vcal = new VObject\Component\VCalendar();
        $vcal->PRODID = '-//ODE Dev//Faker//FR';

        $vevent = $vcal->add('VEVENT');

        $uid = $faker->numerify('ODE-####-####-####-####');
        $datevent = $faker->dateTimeBetween('now', '+1 day');

        $vevent->add('ORGANIZER', $faker->companyEmail);
        $vevent->add('CREATED', $faker->dateTimeBetween('now', 'now'));
        $vevent->add('UID', $uid);
        $vevent->add('TRANSP', array('OPAQUE', 'TRANSPARENT')[rand(0,1)]);
        $vevent->add('SUMMARY', $faker->sentence(2));
        $vevent->add('LOCATION', $faker->streetAddress);
        $vevent->add('DTSTART', $datevent);
        $vevent->add('DTEND', $datevent->add(new \DateInterval('PT1H')));
        $vevent->add('X-ODE-PRICE', sprintf('%d€', $faker->randomFloat(2, 0, 100)));
        $vevent->add('DESCRIPTION', $faker->paragraph(3));

        return $vcal;
    }

    protected function persistEvent($calName, $vcal)
    {   
        $this->setCalendarSCDC($calName);

        $rawcal = $vcal->serialize();

        if ($this->scdClient->create($rawcal) == null)
            throw new \Exception('Can\'t persist the event named "'.$vcal->VEVENT->SUMMARY.'".');

        return $rawcal;
    }

    public function scdcListAction()
    {
        $this->getSimplecalDavClient();

        $calendars = $this->scdClient->findCalendars();
        // die(var_dump($calendars));

        return $this->render('LesPolypodesAppBundle:Events:scdcList.html.twig', array(
            'calendars' => $calendars,
        ));
    }


    public function scdcListEventAction($name)
    {
        $this->getSimplecalDavClient();

        $this->setCalendarSCDC($name);
        $events = $this->scdClient->getEvents();
        
        // foreach ($events as $event)
        // {
        //     var_dump(explode(':', $events));
        // }

        // die(var_dump($events));

        return $this->render('LesPolypodesAppBundle:Events:scdcListEvent.html.twig', array(
            'events' => $events
        ));
    }

     public function scdcListEventRowAction($name)
    {
        $this->getSimplecalDavClient();

        $this->setCalendarSCDC($name);
        $events = $this->scdClient->getEvents();

        return $this->render('LesPolypodesAppBundle:Events:scdcListEventRow.html.twig', array(
            'events' => $events
        ));
    }

    public function createAction()
    {
        $this->getSimplecalDavClient();

        $vcal = $this->createFakeVCal();

        $this->persistEvent($this->maincal_Name, $vcal);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vcal' => $vcal->serialize(),
            'name' => $this->maincal_Name,
        ));
    }

    public function indexAction()
    {
        return $this->render('LesPolypodesAppBundle:Events:index.html.twig');        
    }

    public function readAction()
    {
        // TODO: fetch events between 2 datetimes
        return $this->render('LesPolypodesAppBundle:Events:read.html.twig');
    }

    public function updateAction()
    {
        // TODO: update 1 event
        // TODO: all events between 2 datetimes
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:update.html.twig');
    }

    public function deleteAction()
    {
        // TODO : delete on event
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:delete.html.twig');
    }

    public function formAction(Request $request)
    {
        $this->getSimplecalDavClient();

        $event = new FormCal();
        // Valeurs par défaut
        $event->setName('Nom de l\'évènement');
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));
        $event->setDescription('Décrivez votre évènement');

        $form = $this->createFormBuilder($event)
            ->add('name', 'text')
            ->add('startDate', 'date')
            ->add('endDate', 'date')
            ->add('description', 'textarea')
            ->add('Valider', 'submit')
            ->getForm();
        
        $form->handleRequest($request);

        if($form->isValid())
        {
            $vcal = $this->createVCal($event);

            $this->persistEvent($calendarName, $vcal);

            return $this->redirect($this->generateUrl('les_polypodes_app_list_event_row', array('name' => $this->maincal_Name) ));
            // return $this->forward('LesPolypodesAppBundle:Events:scdcListEvent', array( 'name' => $calendarName, ));
        }
        
        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView()
            ));       
    }

    public function devInsertAction($name, $n)
    {
        $this->getSimplecalDavClient();

        $calendarName = $name;

        for ($i = 0; $i < $n; $i++)
        {
            $vcal = $this->createFakeVCal();

            $this->persistEvent($calendarName, $vcal);
        }

        return $this->forward('LesPolypodesAppBundle:Events:scdcListEvent', array( 'name' => $calendarName, ));
    }
}
