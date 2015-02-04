<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sabre\VObject;
use Faker;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;
use Sabre\DAV;
use LesPolypodes\AppBundle\Entity\FormCal;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class EventsController extends Controller
{
    // Connexion avec Baikal
    protected $caldav_login = null;
    protected $caldav_password = null;
    protected $caldav_host = null;
    protected $caldav_maincal_name = null;

    // protected $caldav_login_baikal = null;
    // protected $caldav_password_baikal = null;
    // protected $caldav_host_baikal = null;
    // protected $caldav_maincal_name_baikal = null;
    //Connexion avec Calendar Server
    // protected $caldav_login_calserv = null;
    // protected $caldav_password_caserv = null;
    // protected $caldav_host_calserv = null;
    // protected $caldav_maincal_name_calserv = null;

    protected $scdClient = null;
    protected $sabreClient = null;


    #TODO: refactor all this as a service!

    protected function getBaikal_CalDavConnection()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $this->caldav_login = $this->container->getParameter('caldav_login_baikal');
        $this->caldav_password = $this->container->getParameter('caldav_password_baikal');
        $this->caldav_host = $this->container->getParameter('caldav_host_baikal');
        $this->caldav_maincal_name = $this->container->getParameter('caldav_maincal_name_baikal');
    }

    protected function getCalserv_CalDavConnection()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $this->caldav_login= $this->container->getParameter('caldav_login_calserv');
        $this->caldav_password = $this->container->getParameter('caldav_password_calserv');
        $this->caldav_host = $this->container->getParameter('caldav_host_calserv');
        $this->caldav_maincal_name = $this->container->getParameter('caldav_maincal_name_calserv');
    }

    protected function getSimplecalDavClient($serv)
    {
        switch($serv)
        {
            case "baikal": 
                $this->getBaikal_CalDavConnection();
                break;
            case "calserv":
                $this->getCalserv_CalDavConnection();
                break;
        }

        $this->scdClient = new SimpleCalDAVClient;
        $url = sprintf("%s%s/", $this->caldav_host, $this->caldav_login);
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
        $vevent->add('X-ODE-PRICE', sprintf('%d€', $event->getPrice()));
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
        // $uid = "dfghjkl";
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

    public function scdcListAction($serv)
    {
        $this->getSimplecalDavClient($serv);

        $calendars = $this->scdClient->findCalendars();

        return $this->render('LesPolypodesAppBundle:Events:scdcList.html.twig', array(
            'calendars' => $calendars,
        ));
    }


    public function scdcListEventAction($name, $serv)
    {
        $this->getSimplecalDavClient($serv);

        $this->setCalendarSCDC($name);
        $events = $this->scdClient->getEvents();
        
        $parser = new VObject\Reader();

        $dataContainer = new \stdClass();
        $dataContainer->vcal = null;
        $dataContainer->dateStart = null;
        $dataContainer->dateEnd = null;

        foreach ($events as $event) {
            $vcal = $parser->read($event->getData());
            $dataContainer->vcal = $vcal;
            $dataContainer->dateStart = (new \datetime($vcal->VEVENT->DTSTART))->format('Y-m-d H:i');
            $dataContainer->dateEnd = (new \datetime($vcal->VEVENT->DTEND))->format('Y-m-d H:i');

            $datas[] = clone $dataContainer;
        }

        return $this->render('LesPolypodesAppBundle:Events:scdcListEvent.html.twig', array(
            'datas' => $datas
        ));
    }

     public function scdcListEventRawAction($name, $serv)
    {
        $this->getSimplecalDavClient($serv);

        $this->setCalendarSCDC($name);
        $events = $this->scdClient->getEvents();

        return $this->render('LesPolypodesAppBundle:Events:scdcListEventRaw.html.twig', array(
            'events' => $events
        ));
    }

    public function createAction($serv)
    {
        $this->getSimplecalDavClient($serv);

        $vcal = $this->createFakeVCal();

        $this->persistEvent($this->caldav_maincal_name_baikal, $vcal);

        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vcal' => $vcal->serialize(),
            'name' => $this->caldav_maincal_name_baikal,
        ));
    }

    public function indexAction($serv)
    {
        return $this->render('LesPolypodesAppBundle:Events:index.html.twig');        
    }

    public function updateAction($serv)
    {
        // TODO: update 1 event
        // TODO: all events between 2 datetimes
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:update.html.twig');
    }

    public function deleteAction($serv)
    {
        // TODO : delete on event
        // ! Think about rollback
        return $this->render('LesPolypodesAppBundle:Events:delete.html.twig');
    }

    public function formAction(Request $request, $serv)
    {
        $this->getSimplecalDavClient($serv);

        $event = new FormCal();
        // Valeurs par défaut
        $event->setName('Nom de l\'évènement');
        $event->setStartDate(new \DateTime('today'));
        $event->setEndDate(new \DateTime('tomorrow'));
        $event->setStartTime(new \DateTime());
        $event->setEndTime((new \DateTime())->add(new \DateInterval('PT1H')));
        $event->setLocation('Adresse de l\'évènement');
        $event->setDescription('Décrivez votre évènement');
        $event->setPrice('€');
        
        $form = $this->createFormBuilder($event)
            ->add('name', 'text')
            ->add('startDate', 'date')
            ->add('endDate', 'date')
            ->add('startTime', 'time')
            ->add('endTime', 'time')
            ->add('location', 'text')
            ->add('description', 'textarea')
            ->add('price', 'text')
            ->add('Valider', 'submit')
            ->getForm();
        
        $form->handleRequest($request);

        // die($caldav_maincal_name_baikal);
        if($form->isValid())
        {
            $vcal = $this->createVCal($event);

            $this->persistEvent($this->caldav_maincal_name_baikal, $vcal);

            return $this->redirect($this->generateUrl('les_polypodes_app_list_event_raw', array('name' => $this->caldav_maincal_name_baikal) ));
        }
        
        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView()
            ));       
    }

    public function devInsertAction($name, $n, $type, $serv)
    {
        $this->getSimplecalDavClient($serv);

        $calendarName = $name;

        switch ($type){
            case 'standard' :
                for ($i = 0; $i < $n; $i++)
                {
                    $vcal = $this->createFakeVCal();

                    $this->persistEvent($calendarName, $vcal);
                }
                break;
            case 'compressed' :
            // Not Working. Send 400 html code when VCAL contains multiple VEVENT with differents UID
                $vcal = new VObject\Component\VCalendar();
                $vcal->PRODID = '-//ODE Dev//Faker//FR';

                for ($i = 0; $i < $n; $i++)
                {
                    $vcal->add($this->createFakeVCal()->VEVENT);
                }

                $this->persistEvent($calendarName, $vcal);
                break;
        }

        return $this->forward('LesPolypodesAppBundle:Events:scdcListEvent', array( 'name' => $calendarName, ));
    }
}
