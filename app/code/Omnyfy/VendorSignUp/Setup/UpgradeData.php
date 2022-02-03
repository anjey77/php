<?php
namespace Omnyfy\VendorSignUp\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\State;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    protected $vendorSetupFactory;

    private $eavSetupFactory;

    private $widgetFactory;

    protected $scopeConfigInterface;

    protected $storeManager;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        \Omnyfy\Vendor\Setup\VendorSetupFactory $vendorSetupFactory,
        \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory,
        EavSetupFactory $eavSetupFactory
    )
    {
        $this->vendorSetupFactory = $vendorSetupFactory;
        $this->widgetFactory = $widgetFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $version = $context->getVersion();
        if (version_compare($version, '1.0.3', '<')) {

            $vendorSetup = $this->vendorSetupFactory->create(['setup' => $setup]);

            $vendorEntity = \Omnyfy\Vendor\Model\Vendor::ENTITY;

            $vendorSetup->updateAttribute(
                $vendorEntity,
                'abn',
                [
                    'is_required' => false
                ]
            );
        }
        $setup->endSetup();
    }
}
