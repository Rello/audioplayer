<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2020 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Search;


/**
 * Provide search results from the 'audioplayer' app
 */
class Provider extends \OCP\Search\Provider {

    /**
     *
     * @param string $query
     * @return array
     */
    function search($query)
    {
        \OC::$server->getLogger()->error('test', ['app' => 'audioplayer']);
        $searchresults = array();
        $results = \OC::$server->query(\OCA\audioplayer\Controller\DbController::class)->search($query);

        foreach ($results as $result) {
            $returnData = array();
            $returnData['id'] = $result['id'];
            $returnData['description'] = \OC::$server->getL10N('audioplayer')->t('Audio Player') . ' - ' . $result['name'];
            $returnData['link'] = '../audioplayer/#' . $result['id'];
            $returnData['icon'] = '../audioplayer/img/app.svg';

            $searchresults[] = new \OCA\audioplayer\Search\Result($returnData);
        }
        return $searchresults;
    }
}
