<?php


namespace Omnyfy\Vendor\Ui\Component\Listing\Product\Column;


use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Omnyfy\Vendor\Model\VendorFactory;

class VendorName extends Column
{
    /**
     * @var VendorFactory
     */
    private $vendorFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param VendorFactory $vendorFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        VendorFactory $vendorFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->vendorFactory = $vendorFactory;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $productId = $item['entity_id'];
                $select = $this->connection->select()->from(
                    $this->connection->getTableName('omnyfy_vendor_vendor_product'),
                    ['vendor_id']
                )->where('product_id = ?',$productId);
                $vendorId = $this->connection->fetchOne($select);
                $vendor = $this->vendorFactory->create()->load($vendorId);
                $item[$this->getData('name')] = $vendor->getName();
            }
        }
        return $dataSource;
    }
}
