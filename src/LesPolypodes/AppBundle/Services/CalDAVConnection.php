<?php
/**
 * Created by PhpStorm.
 * User: ronan
 * Date: 09/02/2015
 * Time: 14:53
 */

namespace LesPolypodes\AppBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAware;


class CalDAVConnection extends ContainerAware 
{
    /* MOVE Here from EventsController:
     * getSimplecalDavClient
     *
     *  all protected properties from EventsController: protected $caldav_login, etc.
     *  all protected methods from EventsController: protected function getBaikal_CalDavConnection, etc.
     *
     *
     */

    // NOTE : getSimplecalDavClient was protected, it becomes public
    // public function getSimplecalDavClient($serv)
    // {
    //     return 'aConnectionToACalDAV';
    // }

    protected $caldav_login = null;
    protected $caldav_password = null;
    protected $caldav_host = null;
    protected $principalDisplayName = null;

    /**
     * @var SimpleCalDAVClient
     */
    protected $scdClient = null;

    protected $sabreClient = null;

    protected function getBaikal_CalDavConnection()
    {
        $caldav = $this->container->getParameter('caldav');
        $this->caldav_login = $caldav['baikal']['login'];
        $this->caldav_password = $caldav['baikal']['password'];
        $this->caldav_host = $caldav['baikal']['host'];

    }

    protected function getCalserv_CalDavConnection()
    {
        $caldav = $this->container->getParameter('caldav');
        $this->caldav_login= $caldav['calserv']['login'];
        $this->caldav_password = $caldav['calserv']['password'];
        $this->caldav_host = $caldav['calserv']['host'];
    }

    public function getSimplecalDavClient($serv)
    {
        switch($serv)
        {
            case "calserv": 
                $this->getCalserv_CalDavConnection();
                break;
            default:
                $this->getBaikal_CalDavConnection();
                break;
        }

        $this->scdClient = new SimpleCalDAVClient;
        $url = sprintf("%s%s/", $this->caldav_host, $this->caldav_login);
        $this->scdClient->connect($url, $this->caldav_login, $this->caldav_password);

        $this->principalDisplayName = $this->scdClient->getPrincipalDisplayName();

        return $this->scdClient;
    }

    public function setCalendarSCDC($name)
    {
        $calendarID = $this->scdClient->findCalendarIDByName($name);

        if ($calendarID == null) {
            throw new \Exception('No calendar found with the name "'.$name.'".');
        }

        $this->scdClient->setCalendar($this->scdClient->findCalendars()[$calendarID]);
    }

    public function getPrincipalDisplayName()
    {
        return $this->principalDisplayName;
    }
}