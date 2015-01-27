<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sabre\VObject;
use Faker;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;
use Sabre\DAV;

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

        // MOVE THIS TO app/config/parameters.yml
        $username = 'yolan';    //'admin'
        $password = 'yolan';    //'password'
        $urlbase = 'http://baikal/app_dev.php/dav/cal/calendars/';
        // END MOVE

        $url = sprintf("%s%s", $urlbase, $username);
        $this->scdClient->connect($url, $username, $password);

        $calendarName = $name;
        $calendarID = $this->scdClient->findCalendarIDByName($calendarName);

        if ($calendarID == null) {
            throw new \Exception('No calendar found with the name "'.$calendarName.'".');
        }

        $this->scdClient->setCalendar($this->scdClient->findCalendars()[$calendarID]);
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
        $vcard = new VObject\Component\VCard([
            'FN'    => $faker->name,
            'TEL'   => $faker->phoneNumber,
            'EMAIL' => $faker->companyEmail,
        ]);
        $vcard->add('TEL', $faker->phoneNumber, ['type' => 'fax']);

        $vcal = new VObject\Component\VCalendar();
        $vcal->add('VEVENT', [
            'SUMMARY' => $faker->sentence(),
            'DTSTART' => $faker->dateTimeBetween('-2 years', 'now'),
            'RRULE' => 'FREQ=YEARLY',
        ]);
        $vcal->add('ORGANIZER', 'mailto:'.$faker->companyEmail);
        $vcal->add('ATTENDEE', 'mailto:'.$faker->freeEmail);
        $vcal->add('ATTENDEE', 'mailto:'.$faker->freeEmail);

        // TODO:
        $result = false;
        // 1 - Persist iCal Event into a remote WebCAL server
        //$result = actionThatSaveiCalIntoCalDAV($vcal);
        // 2 - Then display a confirmation message with created event:


        return $this->render('LesPolypodesAppBundle:Events:create.html.twig', array(
            'vcard' => $vcard->serialize(),
            'vcal' => $vcal->serialize(),
            'result' => (int) $result,
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
}
