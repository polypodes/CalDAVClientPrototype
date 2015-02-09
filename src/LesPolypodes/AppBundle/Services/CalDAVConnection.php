<?php
/**
 * Created by PhpStorm.
 * User: ronan
 * Date: 09/02/2015
 * Time: 14:53
 */

namespace LesPolypodes\AppBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAware;


class CalDAVConnection extends ContainerAware {


    /* MOVE Here from EventsController:
     * getSimplecalDavClient
     *
     *  all protected properties from EventsController: protected $caldav_login, etc.
     *  all protected methods from EventsController: protected function getBaikal_CalDavConnection, etc.
     *
     *
     */

    // NOTE : getSimplecalDavClient was protected, it becomes public
    public function getSimplecalDavClient($serv)
    {
        return 'aConnectionToACalDAV';
    }
}