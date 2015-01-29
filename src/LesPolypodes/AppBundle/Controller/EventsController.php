<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\DateTime;
use Sabre\VObject;
use Faker;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;
use Sabre\DAV;
use LesPolypodes\AppBundle\Entity\Task;
use Symfony\Component\Form\Form;

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


    public function sabreListEventAction() {
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
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');

        // see http://sabre.io/vobject/usage/
        // $vcard = new VObject\Component\VCard([
        //     'FN'    => $faker->name,
        //     'TEL'   => $faker->phoneNumber,
        //     'EMAIL' => $faker->companyEmail,
        // ]);
        // $vcard->add('TEL', $faker->phoneNumber, ['type' => 'fax']);

        // $vcal = new VObject\Component\VCalendar();
        // $vcal->add('VEVENT', [
        //     'SUMMARY' => $faker->sentence(3),
        //     'DTSTART' => $faker->dateTimeBetween('now', '+1 day'),
        //     'X-ODE-PRICE' => $faker->randomFloat(2, 0, 100),
        // ]);

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

        // TODO:
        $result = false;
        // 1 - Persist iCal Event into a remote WebCAL server
        //$result = actionThatSaveiCalIntoCalDAV($vcal);
        // 2 - Then display a confirmation message with created event:

        $calendarName = 'ODE Test 1';
        $this->setCalendarSCDC($calendarName);

        if ($this->scdClient->create($vcal->serialize()) != null)
            $result = true;


        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            // 'vcard' => $vcard->serialize(),
            'vcal' => $vcal->serialize(),
            'result' => (int) $result,
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

    public function formAction()
    {
        $task = new Task();
        // Valeurs par défaut
        $task->setName('Nom de l\'évènement');
        $task->setStartDate(new \DateTime('today'));
        $task->setEndDate(new \DateTime('tomorrow'));
        $task->setDescription('Décrivez votre évènement');

        $form = $this->createFormBuilder($task)
            ->add('name', 'text')
            ->add('startDate', 'date')
            ->add('endDate', 'date')
            ->add('description', 'textarea')
            ->add('Valider', 'submit')
            ->getForm();
        // TODO: 
        return $this->render('LesPolypodesAppBundle:Events:form.html.twig', array(
            'form' => $form->createView()
            ));
    }
}
