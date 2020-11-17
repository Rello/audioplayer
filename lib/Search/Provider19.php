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

use OCA\audioplayer\Controller\DbController;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * Provide search results from the 'audioplayer' app
 */
class Provider19 extends \OCP\Search\Provider
{

    /** @var IL10N */
    private $l10n;

    /** @var IURLGenerator */
    private $urlGenerator;

    private $DBController;

    public function __construct(IL10N $l10n,
                                IURLGenerator $urlGenerator,
                                DBController $DBController)
    {
        $this->l10n = $l10n;
        $this->urlGenerator = $urlGenerator;
        $this->DBController = $DBController;
    }

    /**
     *
     * @param string $query
     * @return array
     */
    function search($query)
    {
        $searchresults = array();
        $results = $this->DBController->search($query);

        foreach ($results as $result) {
            $returnData = array();
            $returnData['id'] = $result['id'];
            $returnData['description'] = $this->l10n->t('Audio Player') . ' - ' . $result['name'];
            $returnData['link'] = '../audioplayer/#' . $result['id'];
            $returnData['icon'] = '../audioplayer/img/app.svg';

            $searchresults[] = new \OCA\audioplayer\Search\Result($returnData);
        }
        return $searchresults;
    }
}
