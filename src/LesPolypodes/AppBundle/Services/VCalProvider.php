<?php

namespace LesPolypodes\AppBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAware;
use Sabre\VObject;
use Faker;
use Sabre\DAV;

/**
 * Class VCalProvider
 * @package LesPolypodes\AppBundle\Services
 */
class VCalProvider extends ContainerAware {

    /**
     * @param $event
     *
     * @return VObject\Component\VCalendar
     */
    public function createVCal($event)
    {
        $faker = Faker\Factory::create('fr_FR');

        $vcal = new VObject\Component\VCalendar();
        $vcal->PRODID = '-//ODE Dev//Form FR';

        $vtimezone = $vcal->add('VTIMEZONE');
        $vtimezone->add('TZID', 'Europe/London');

        $vtimezone->add('BEGIN', 'DAYLIGHT');
        $vtimezone->add('TZOFFSETFROM', '+0000');
        $vtimezone->add('TZOFFSETTO', '+0000');
        $vtimezone->add('DTSTART', (new \DateTime())->format('Ymd\THis'));
        $vtimezone->add('END', 'DAYLIGHT');

        $vevent = $vcal->add('VEVENT');

        $uid = $faker->numerify('ODE-####-####-####-####');

        $vevent->add('ORGANIZER', $event->getOrganizer());
        $vevent->add('CREATED', (new \DateTime())->format('Ymd\THis\Z'));
        $vevent->add('DTSTAMP', (new \DateTime())->format('Ymd\THis\Z'));
        $vevent->add('UID', $uid);
        // $vevent->add('TRANSP', array('OPAQUE', 'TRANSPARENT')[rand(0,1)]);
        $vevent->add('SUMMARY', $event->getName());
        $vevent->add('LOCATION', $event->getLocation());
        $vevent->add('DTSTART', $event->getStartDate()->format('Ymd\THis'));
        $vevent->add('DTEND', $event->getEndDate()->format('Ymd\THis'));
        $vevent->add('X-ODE-PRICE', sprintf('%d€', $event->getPrice()));
        $vevent->add('DESCRIPTION', $event->getDescription());

        return $vcal;
    }

    /**
     * @return VObject\Component\VCalendar
     */
    public function createFakeVCal()
    {
        // see: https://github.com/fzaninotto/Faker
        $faker = Faker\Factory::create('fr_FR');

        $vcal = new VObject\Component\VCalendar();
        $vcal->PRODID = '-//ODE Dev//Faker FR';

        $vtimezone = $vcal->add('VTIMEZONE');
        $vtimezone->add('TZID', 'Europe/London');

        $vtimezone->add('BEGIN', 'DAYLIGHT');
        $vtimezone->add('TZOFFSETFROM', '+0000');
        $vtimezone->add('TZOFFSETTO', '+0000');
        $vtimezone->add('DTSTART', (new \DateTime())->format('Ymd\THis'));
        $vtimezone->add('END', 'DAYLIGHT');

        $vevent = $vcal->add('VEVENT');

        $uid = $faker->numerify('ODE-####-####-####-####');

        $datevent = $faker->dateTimeBetween('now', '+1 day');
        $transparencies = array('OPAQUE', 'TRANSPARENT');
        $transparency = array_rand($transparencies);
        $vevent->add('ORGANIZER', $faker->companyEmail);
        $vevent->add('CREATED', (new \DateTime())->format('Ymd\THis\Z'));
        $vevent->add('DTSTAMP', (new \DateTime())->format('Ymd\THis\Z'));
        $vevent->add('UID', $uid);
        $vevent->add('TRANSP', $transparency);
        $vevent->add('SUMMARY', $faker->sentence(2));
        $vevent->add('LOCATION', $faker->streetAddress);
        $vevent->add('DTSTART', $datevent->format('Ymd\THis'));
        $vevent->add('DTEND', $datevent->add(new \DateInterval('PT1H'))->format('Ymd\THis'));
        $vevent->add('X-ODE-PRICE', sprintf('%d€', $faker->randomFloat(2, 0, 100)));
        $vevent->add('DESCRIPTION', $faker->paragraph(3));

        return $vcal;
    }
}