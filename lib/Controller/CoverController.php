<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @author Sebastian Doell <sebastian@libasys.de>
 * @copyright 2016-2021 Marcel Scherello
 * @copyright 2015 Sebastian Doell
 */

namespace OCA\audioplayer\Controller;

use OCP\AppFramework\Controller;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use \OCA\audioplayer\Http\ImageResponse;


class CoverController extends Controller
{

    private $userId;
    private $l10n;
    private $logger;
    private $DBController;
    private $db;

    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        IL10N $l10n,
        IDBConnection $db,
        LoggerInterface $logger,
        DbController $DBController
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->db = $db;
        $this->logger = $logger;
        $this->DBController = $DBController;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param $album
     * @return ImageResponse
     */
    public function getCover($album)
    {
        $cover = '';
        $SQL = "SELECT  `cover`, `name`, `artist_id`
				FROM `*PREFIX*audioplayer_albums` 
			 	WHERE  `user_id` = ? AND `id` = ? 
			 	";

        $stmt = $this->db->prepare($SQL);
        $stmt->execute(array($this->userId, $album));
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            $artist = $this->DBController->loadArtistsToAlbum($album, $row['artist_id']);
            $cover = $row['cover'];
            if ($row['name'] === $this->l10n->t('Unknown') AND $artist === $this->l10n->t('Various Artists')) {
                $cover = 'iVBORw0KGgoAAAANSUhEUgAAAPoAAAD6CAIAAAAHjs1qAAAAK3RFWHRDcmVhdGlvbiBUaW1lAERpIDE1IE5vdiAyMDE2IDExOjU3OjA0ICswMTAwCiwhogAAAAd0SU1FB+ALDwo7AioIm6cAAAAJcEhZcwAALiMAAC4jAXilP3YAAAAEZ0FNQQAAsY8L/GEFAAAO2UlEQVR42u3d+1NU9R/H8f5EWG5ykzukZUR4wVBD00olsxwzvDRUIxNUViQzFZE6EGmlNSqokBd0FKPClDtetvfgfBu/nvf7s8vu+ZzP4bxfjx+dz9mjZ5+7fnb3c855JgNAjWdc/wUAgoPcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHJ3oLS09Ny5c7OzsyMjIytWrAhgjzU1NS+++GJeXp7rf7pjyD1oVN7Nmzfj/3Pnzp26ujp7uyssLOzr65ucnKRX1+jo6I4dO1wfAJeQe6AaGhoePHgQ/39zc3NFRUU2dkdv5+Pj40/t7t1333V9GJxB7sF566234oL9+/fb2OPhw4fZ3dXX17s+GG4g94C0tbXFZd3d3TZ2OjAwwO5uYmIiPz/f9SFxALlbF4vFenp64kbHjx+3sesLFy5Ie+zv73d9YBxA7nbl5uYODg7GE7GU+6FDhww7bW5udn14gobcLSorKxsbG0vYur3cc3Jy/vzzT2mn8/PzJSUlrg9SoJC7LXV1dTMzM8m0bi938vzzzz969Eja788//+z6OAUKuVuxZcuWJEO3nTvZs2ePYdf0V3V9tIKD3P23f//+RbVuO3dC7+LSrsfHx2nO4/qYBQS5++zo0aOLbT393GtqatauXVtZWSkNKCgomJ6elvb+wQcfuD5sAUHuvsnOzv7ll19SaD2d3IuLi8+fP//f45w5c0b6Qv2NN96Q9n7//n16HNfHLwjI3R9FRUVXr15NrfWUc4/FYrdu3Xrqoa5du0Z/zo43vBo7OztdH8IgIHcfrFix4t69eym3nnLur776KvtobW1t7Hia7Ujf0tCfl5aWuj6Q1iH3dDU2NtJkIJ3WU879yJEj0gO+8MIL7CYdHR2a3+CRe1reeeedNENPJ3fDV0Cjo6NZWVneTWhmL31mpRetpYWZ4YHcU/fxxx/70nrKuVdVVRkes7W1ld3K8CKRNokM5J4K+ix47Ngxv1pPOXfy2WefSY8pfd+Sk5MzMTHBbnL37t3s7GzXR9ci5L5oeXl5Fy9e9LH1dHKnGYv3y5n/9PT0sFu9//770iZbt251fYAtQu6LU1FRYVh0FXzupLa21vDIK1eu9G6ybNmyubk5dvzZs2ddH2OLkPsirF69OvlVX4HlnmGc0vT19bGbGH79LSsrc32kbUHuydq2bZuN0H3JneZXk5OT0oNXV1d7N6mpqZHGHzx40PXBtgW5J8V8noS93Glqvnv37t7e3m+++WbDhg2GvyENkx7822+/ZTcZGhpix4+MjLg+3rZEM/ecnJxcn9A0t6ury2rrUu4FBQVPfQyVPnqSzMzMJ6/n8aQHDx4UFhZ6N3nzzTelv09VVZXr59CKqOW+a9eu69evz/hkenpa+kgXQO6nTp3yjty3b5/0b9++fbv0+Hv27PGOp9eA9yogj+3du9f1M2lFpHI3/Kgect7ci4qK2PUt8/Pz7Ft1xsKSzDt37rCPf+3aNXaTM2fOsON/++0310+mFdHJvbW11XW0fuZu+MWU5vHSQWhpaZG2Yr+RbG5uZgfT/2k0i3P9lPovOrkbTl8IP2/ueXl5s7Oz0viKigr2IND/CdL8pKOjwzu+pKRE2sWaNWtcP6X+i07urov1OXfyxRdfSOOlL1vIyZMn2U3oIw07nuY57PhIrp9B7qHA5k7TiampKXY8vYVLqxc3bdok7YX9/Uj6wBPJn1eReyhI37sbFhhLl5WkWZD00+/bb7/tHb9x40Z28MTERPSWiyH3UDD8zCR92XLr1q3MzEx2q9OnT7Ob/PTTT97By5cvl05xMpzrvUQh91AwLCI4cOCAtJV0KwTpUsP0yvGe8xGLxf766y92fFNTk+tn1WfIPRQMuRcWFkonB3700UfsJhUVFdKO2K90pKvQRG/xDHIPBfMSsb6+Pnar4eFhdrxhCvTKK694x7e3t7ODDd//LFHIPRTMuVOj7FbSYpgM+Rob7BWUdu7cyQ4eHBx0/az6DLmHgjn3ZcuWSfOZdevWsZtId084ceKEd/BLL73EDqZPwxG7exlyD4WE690vXbrEbtjS0sKOl1bns/Of0tJSdvDExMSzzz7r+on1E3IPhYS5S+crSUuCn3vuOXY8++VMfn4++7/H3NyceZH9koPcQyFh7q+//jq74cWLF9nx0mKY2dlZ7/V+6QVw9+5d7+CHDx9G7MsZ5B4KCXNftWoVu+HY2Bj7YxPNuefn573jHz16tHz58qcG0yP88ccf7OBPP/3U9RPrJ+QeCglzLy8vZzecmZnJzc31jo/FYuwbdlz4rfTGjRts7hH7LhK5h0LC3Ondml3ZS3PugoIC73jDb6XsmdrS5Ytt32chYMg9FBJWRW/h7GmENL1ml0ZS7tL1cGpqarzjpW9+InZaE3IPhWRyZ9c50nyDzZ2m41Lu7HeL586dYwcPDQ25fmL9hNxDIZnc2Y+eNMNZ7GSGfXeXJjO9vb2un1g/IfdQSJi74Uxt9qRSyl1aNsNeVGNkZIQdbLjUx1KE3EMhYe40A2E3HB8fZ29Nk52dLZ286z2niWY+7HVV6QX21VdfuX5i/YTcQyFh7g0NDeyGN27cYMcXFxez/xvQ5Mf7vwHl/vfff3sH0+dgaY3xEoXcQyFh7tJV+6TbXldXV7Pj7927511EIF31gF4bzc3Nrp9YPyH3UEiYe39/P7the3s7O76xsZEdz16PQPpgMDU1tWrVKtdPrJ+QeyiYc5fWtJDXXnuN3WTfvn3s+NOnT3sHr1y5kh1MM5ySkhLXT6yfkHsomHOXljfG5WuX0gOy448cOeIdvGHDBnbw5cuXpVu0LlHIPRTMuUvnatC7L5uj9E0L2blzp3e8dPY3e+WCJQ25h4Ihdwp6bGyM3Ur6UtxwaV/24gXfffcdO5i9zt6ShtxDwZD7+vXrpa02bdrEbiJ9azkzM+Nd7J4h/6S6Y8cO18+qz5B7KBhyl66RJC39zZAvLnnp0iXvYGllPKmtrXX9rPoMuYeClLt0Vgfp7u5mN6GJ++3bt9lN2MmJtAt6DeTn57t+Vn2G3ENByn1gYEDaRHrrlZYbkLq6Ou/49957jx0sXTF4SUPuocDmLl1ehly5ckU6DtKt66emptiJu3RFmq6uLtdPqf+ik7uNu/u6zf3333+Xxm/evJk9CFlZWePj4+wmP/zwg3e8tIyebNu2zfVT6r/o5L5+/XpL9/h1kju9E0ufIGmaIV37V/pORsp3zZo10vjy8nLXT6n/opN7xsKlyqVrN4ecN3d6n5buZUAvbOkI/Prrr+wm09PT7Nc4nZ2d7PibN29Kr6glLVK5k7KyssOHDx9fcCxt33//vXQvF9u5E/oLeEeeOnVK+rcb1hp8/fXX3vGGmc/nn3/u+pm0Imq5+y4Wi/X09DjJvbi4+Kkzkv755x/6Q+mveuLECenx2a9x6uvrpfHSpSeXOuSelA8//DD43DMWfgNqb28fGRmh+XpHR4fh5o/Sqsb4wkovdpPu7m52/OTkJPsdTgQg92Tt2rUr+NyTd/bsWenB2Q+phls4Rez81Cch90Wgz4jStyVuc29qapIe+fbt2+yqyd27d0ubRHUmk4HcF6u6upom0KHKPTs7mz3T9DH27LvMzEyaHbHjpUXF0YDcF62wsNDwA1DwuR89elR62NHRUbbdtWvXSpu0tbW5PsAWIfdU0Bvqjz/+GIbcDcuDSWNjI7vV+fPnpU3YWw1HBnJP3Zdffuk2d3rnZi9U/Zh0eUfp1jTk5MmTrg+qXcg9LS0tLQ5zp8+UhseU7jMzODgobUKvBNdH1C7knq7Nmzenv3IhtdylO+aR1tZWdhPDKkv25I+IQe4+qK2tlda3WM1d+ll0eHiYXfGSlZUlnflBInYbJhZy90dpaeno6GjAudPc3ft9Iv1XI12NwzD1ku5IHDHI3Te5ubnSVdIt5Z6xsCTuyWtszM/Pb9y4kR1JL0jp8gSkoaHB9fELAnL3E00hpIUolnLPWJiiNDU1HTp0aPv27YazS6W1wfHI3aLDALn7L4X1ZLZvgWRYMhAXLj4TScjdiubm5vDkXllZaZjGdHZ2uj5awUHutqxbty759WT2cqepjnRnjvjCbeDz8vJcH6rgIHeLqqqqpCv3BpZ7V1eXYb9bt251fZAChdztKigoGB4edpV7bW2tYaf9/f2uD0/QkLt1NJ2QbkZgO/dPPvlE2uPk5GT0LhKWEHIPiHTO/2OWTiCSLu0blxdLRhtyD450Rw1y8OBBG3uUvn+UbnETecg9UFu2bPGuJ7t//77h+gLpyMnJ8Z57NTAwEMlryCQDuQeNPj4+uU6L5tAvv/yyvd1VVlYODQ09fo09fPiwr68vqlcZSAZyd6C8vPzy5cvxhRND6+vrbe+OPivTZKm3t5fmNq7/6Y4hd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDoogd1AEuYMiyB0UQe6gCHIHRZA7KILcQRHkDor8C3D7LDKaAfDjAAAAAElFTkSuQmCC';
            }
        }
        $imageData = base64_decode($cover);
        return new ImageResponse(array('mimetype' => 'image/jpg', 'content' => $imageData));
    }
}
