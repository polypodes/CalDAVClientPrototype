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
    protected $scdClient = null;
    protected $sabreClient = null;


    protected function getCalDavConnection()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $this->caldav_login = $this->container->getParameter('caldav_login'); //'admin'
        $this->caldav_password = $this->container->getParameter('caldav_password'); //'admin'
        $this->caldav_host = $this->container->getParameter('caldav_host'); //'admin'
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
        $this->getSimplecalDavClient();

        $calendarName = $name;
        $calendarID = $this->scdClient->findCalendarIDByName($calendarName);

        if ($calendarID == null) {
            throw new \Exception('No calendar found with the name "'.$calendarName.'".');
        }

        $this->scdClient->setCalendar($this->scdClient->findCalendars()[$calendarID]);
    }

    protected function createFakeVCal()
    {
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');

        $vcal = new VObject\Component\VCalendar();
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
        $this->setCalendarSCDC($name);
        $events = $this->scdClient->getEvents();

        // die(var_dump($events));

        return $this->render('LesPolypodesAppBundle:Events:scdcListEvent.html.twig', array(
            'events' => $events
        ));
    }


    public function sabreListAction()
    {

        $username = 'yolan';    //'admin'
        $password = 'yolan';    //'password'
        $urlbase = 'http://baikal/app_dev.php/dav/cal/calendars/';

        $settings = array(
            'baseUri' => $urlbase.$username.'/',
            'userName' => $username,
            'password' => $password,
        );

        $client = new DAV\Client($settings);

        // Réccupère les events du calendar
        $calendars = $client->propfind(null, array('{DAV:}displayname'), 1);
        // die(var_dump($calendars));

        // $calendars = $client->request('PROPFIND');
        // die(var_dump($calendars['body']));

        return $this->render('LesPolypodesAppBundle:Events:sabreList.html.twig', array(
            'calendars' => $calendars
        ));
    }


    public function sabreListEventAction() 
    {
        // http://sabre.io/dav/davclient/
        $settings = array(
            'baseUri' => 'http://baikal/cal.php/calendars/yolan/',
            'userName' => 'yolan',
            'password' => 'yolan',
        );

        $client = new DAV\Client($settings);

        // Réccupère les events du calendar
        $events = $client->propfind('test', array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
        ));

        return $this->render('LesPolypodesAppBundle:Events:sabreListEvent.html.twig', array(
            'events' => $events
        ));
    }


    public function createAction()
    {
        $calendarName = 'ODE Test 2';

        $vcal = $this->createFakeVCal();

        $this->persistEvent($calendarName, $vcal);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vcal' => $vcal->serialize(),
            'name' => $calendarName,
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
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($event);
            // $em->flush();
            die(var_dump($event));
            return $this->redirect($this->generateUrl('les_polypodes_app_sabre_list' /*, array('id' => $event->getId())*/));
        }
        
        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView()
            ));       
    }

    public function devInsertAction($name, $n)
    {
        $calendarName = $name;

        for ($i = 0; $i < $n; $i++)
        {
            $vcal = $this->createFakeVCal();

            $rawcal = $this->persistEvent($calendarName, $vcal);

            $cals[] = $rawcal;
        }

        return $this->forward('LesPolypodesAppBundle:Events:scdcListEvent', array( 'name' => $calendarName, ));
    }
}
