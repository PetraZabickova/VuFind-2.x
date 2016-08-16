<?php
/**
 * Help view helper
 *
 * PHP version 5
 *
 * Copyright (C) Moravian Library 2016.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Martin Kravec <martin.kravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 */
namespace CPK\View\Helper\CPK;

use Zend\Config\Config;
use CPK\Db\Table\PortalPage;

/**
 * Portal pages view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Martin Kravec <martin.kravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-3.0.php GNU General Public License
 */
class Help extends \Zend\View\Helper\AbstractHelper
{
    /**
     * @var \Zend\Config\Config $config
     */
    protected $config;

    /**
     * @var CPK\Db\Table\PortalPage $portalPageTable,
     */
    protected $portalPageTable;

    /**
     * @var string  $languageCode
     */
    protected $languageCode;

    /**
     * Constructor
     *
     * @param   \Zend\Config\Config     $config
     * @param   CPK\Db\Table\PortalPage $portalPageTable
     * @param   string                  $languageCode
     */
    public function __construct(
        \Zend\Config\Config $config,
        \CPK\Db\Table\PortalPage $portalPageTable,
        $languageCode
    )
    {
        $this->config = $config->toArray();
        $this->portalPageTable = $portalPageTable;
        $this->languageCode = $languageCode;
    }

    /**
     * Get questionmark help
     *
     * @param   string  $pageName
     *
     * @return  string
     */
    public function getQuestionMarkHelp($pageName)
    {
        if (! $this->config['Help']['questionmark_help_enabled']) {
            return '';
        }

        $prettyUrl = $pageName.'-'.explode("-", $this->languageCode)[0];

        $portalPage = $this->portalPageTable->getPage($prettyUrl, $this->languageCode);

        return $this->view->render(
            'Help/questionmark-help.phtml',
            [
                'pageName' => $pageName,
                'portalPage' => $portalPage
            ]
        );
    }

    /**
     * Get element help (tooltip)
     *
     * @param   string  $translationKey
     * @param   string  $element        HTML
     * @param   string  $source         mzk
     *
     * @return  string
     */
    public function getElementHelp($translationKey, $element, $source)
    {
        if (! $this->config['Help']['element_help_enabled']) {
            return '';
        }

        return $this->view->render(
            'Help/element-help.phtml',
            [
                'translationKey' => $translationKey,
                'element' => $element,
                'source' => $source
            ]
        );
    }
}