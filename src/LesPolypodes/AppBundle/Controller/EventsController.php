<?php

namespace LesPolypodes\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sabre\VObject;
use Faker;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;
use Sabre\DAV;

class EventsController extends Controller
{
    public function listAction()
    {
        $myClient = new SimpleCalDAVClient;

        // MOVE THIS TO app/config/parameters.yml
        $username = 'yolan';
        $password = 'yolan';
        $calendarName = 'test'; //laisser vide si inconnu
        $urlbase = 'http://192.168.1.32/cal.php/calendars/';
        // END MOVE

        $url = sprintf("%s%s/%s", $urlbase, $username, $calendarName);
        $myClient->connect($url, $username, $password);
        $myClient->setCalendar($myClient->findCalendars()[$calendarName]);
        $events = $myClient->getEvents();

        return $this->render('LesPolypodesAppBundle:Events:list.html.twig', array(
            'events' => $events
        ));
    }


    public function sabreListAction() {
        // http://sabre.io/dav/davclient/
        $settings = array(
            'baseUri' => 'http://192.168.1.32/cal.php/',
            //'baseUri' => 'http://192.168.1.32/cal.php/calendars/',
            'userName' => 'yolan',
            'password' => 'yolan',
        );

        $client = new DAV\Client($settings);
        $events = $client->propfind('calendars/yolan/test', array(
            '{DAV:}displayname',
            '{DAV:}getcontentlength',
        ));
        die(var_dump($events));
        return $this->render('LesPolypodesAppBundle:Events:sabreList.html.twig', array(
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
