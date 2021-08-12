<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2021 Marcel Scherello
 */

namespace OCA\audioplayer\Dashboard;

use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

class Widget implements IWidget
{

    /** @var IURLGenerator */
    private $url;
    /** @var IL10N */
    private $l10n;

    public function __construct(
        IURLGenerator $url,
        IL10N $l10n
    )
    {
        $this->url = $url;
        $this->l10n = $l10n;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return 'audioplayer';
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->l10n->t('Audio Player');
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function getIconClass(): string
    {
        return 'icon-audioplayer';
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function load(): void
    {
        Util::addScript('audioplayer', 'dashboard');
        Util::addStyle('audioplayer', 'dashboard');
    }
}