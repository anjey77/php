<?php
/**
 * Copyright © 2016 Ihor Vansach (ihor@omnyfy.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Omnyfy\Cms\Block\Sidebar;

/**
 * Cms sidebar rss
 */
class Rss extends \Magento\Framework\View\Element\Template
{
    use Widget;

    /**
     * @var string
     */
    protected $_widgetKey = 'rss_feed';

    /**
     * Available months
     * @var array
     */
    protected $_months;

    /**
     * Retrieve cms identities
     * @return array
     */
    public function getIdentities()
    {
        return [\Magento\Cms\Model\Block::CACHE_TAG . '_cms_rss_widget'  ];
    }

}
