<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-rma
 * @version   2.1.26
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Rma\Plugin;

use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolver\StrategyResolver;

/**
 * @see StrategyResolver::resolve()
 */
class EnableLegacyModePlugin
{
    private $allowedVariables = [
        'rma.getHasItemsWithReason',
        'rma.getHasItemsWithConditions',
        'rma.getHasItemsWithResolution',
    ];

    /**
     * @param StrategyResolver $template
     * @param \callable        $proceed
     * @param string           $value
     * @param Template         $filter
     * @param array            $templateVariables
     *
     * @return mixed
     */
    public function aroundResolve(StrategyResolver $template, $proceed, $value, Template $filter, array $templateVariables)
    {
        $prevMode = $filter->isStrictMode();

        $hasVariable = false;
        foreach ($this->allowedVariables as $allowedVariable) {
            if (strpos($value, $allowedVariable) !== false) {
                $hasVariable = true;
            }
        }

        if ($hasVariable) {
            $filter->setStrictMode(false);
        }

        $result = $proceed($value, $filter, $templateVariables);

        if ($prevMode && $hasVariable) {
            $filter->setStrictMode(true);
        }

        return $result;
    }
}
