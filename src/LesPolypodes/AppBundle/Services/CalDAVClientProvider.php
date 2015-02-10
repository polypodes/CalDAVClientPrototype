<?php
/**
 * Created by PhpStorm.
 * User: ronan
 * Date: 09/02/2015
 * Time: 14:53
 */

namespace LesPolypodes\AppBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAware;
use LesPolypodes\AppBundle\Services\CalDAV\SimpleCalDAVClient;


class CalDAVClientProvider extends ContainerAware
{
    /* MOVE Here from EventsController:
     * getSimplecalDavClient
     *
     *  all protected properties from EventsController: protected $caldav_login, etc.
     *  all protected methods from EventsController: protected function getBaikal_CalDavConnection, etc.
     *
     *
     */

    //TODO: understand this:
    // NOTE : getSimplecalDavClient was protected, it becomes public
    // public function getSimplecalDavClient($serv)
    // {
    //     return 'aConnectionToACalDAV';
    // }

    protected $caldav_login = null;
    protected $caldav_password = null;
    protected $caldav_host = null;
    protected $caldav_maincal_name = null;

    /**
     * @return string caldav maincal name
     */
    public function getCaldavMainCalName() {
        return $this->caldav_maincal_name;
    }

    /**
     * @var SimpleCalDAVClient
     */
    protected $scdClient = null;

    /**
     * @return SimpleCalDAVClient
     */
    public function getScdClient() {
        return $this->scdClient;
    }

    protected $sabreClient = null;

    protected function getBaikal_CalDavConnection()
    {
        $caldav = $this->container->getParameter('caldav');
        $this->caldav_login = $caldav['baikal']['login'];
        $this->caldav_password = $caldav['baikal']['password'];
        $this->caldav_host = $caldav['baikal']['host'];
        $this->caldav_maincal_name = $caldav['baikal']['maincal_name'];
    }

    protected function getCalserv_CalDavConnection()
    {
        $caldav = $this->container->getParameter('caldav');
        $this->caldav_login= $caldav['calserv']['login'];
        $this->caldav_password = $caldav['calserv']['password'];
        $this->caldav_host = $caldav['calserv']['host'];
        $this->caldav_maincal_name = $caldav['calserv']['maincal_name'];
    }

    /**
     * @param $serv
     *
     * @return SimpleCalDAVClient
     * @throws CalDAV\CalDAVException
     */
    public function getClient($serv)
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

        return $this->scdClient;
    }

}