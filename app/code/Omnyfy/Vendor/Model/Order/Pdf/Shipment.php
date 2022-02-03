<?php
namespace Omnyfy\Vendor\Model\Order\Pdf;

use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;

class Shipment extends \Magento\Sales\Model\Order\Pdf\Shipment
{
    const SHIPMENT_BY_MO = 1;
    /**
     * @var \Omnyfy\Vendor\Helper\Product
     */
    protected $_productHelper;

    /**
     * @var \Omnyfy\Vendor\Helper\Data
     */
    protected $_helper;

    protected $_productRepository;

    protected $_vendorFactory;

    protected $_resource;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Omnyfy\Vendor\Helper\Product $productHelper,
        \Omnyfy\Vendor\Helper\Data $helper,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Omnyfy\Vendor\Model\VendorFactory $vendorFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    )
    {
        $this->_storeManager = $storeManager;
        $this->_localeResolver = $localeResolver;
        $this->_productHelper = $productHelper;
        $this->_helper = $helper;
        $this->_productRepository = $productRepository;
        $this->_vendorFactory = $vendorFactory;
        $this->_resource = $resource;
        parent::__construct($paymentData, $string, $scopeConfig, $filesystem, $pdfConfig, $pdfTotalFactory, $pdfItemsFactory, $localeDate, $inlineTranslation, $addressRenderer, $storeManager, $localeResolver, $data);
    }

    /**
     * @return bool
     */
    protected function isPrintAttributesAllowed()
    {
        return (bool)$this->configProvider->isIncludeToShipmentPdf();
    }

    protected function isShipmentByMo()
    {
        $shipmentBy = $this->_scopeConfig->getValue(\Omnyfy\Vendor\Model\Config::XML_PATH_SHIPMENT_BY);
        return $shipmentBy == self::SHIPMENT_BY_MO ? true : false;
    }

    protected function getMoAbn()
    {
        return $this->_scopeConfig->getValue(\Omnyfy\Vendor\Model\Config::XML_PATH_MO_ABN);
    }

    protected function getMoName()
    {
        return $this->_helper->getMoName();
    }

    /**
     * Return PDF document
     *
     * @param \Magento\Sales\Model\Order\Shipment[] $shipments
     * @return \Zend_Pdf
     */
    public function getPdf($shipments = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                $this->_localeResolver->emulate($shipment->getStoreId());
                $this->_storeManager->setCurrentStore($shipment->getStoreId());
            }

            if ($this->isShipmentByMo()) {
                $page = $this->newPage();
                $order = $shipment->getOrder();
                /* Add image */
                $this->insertLogo($page, $shipment->getStore());
                /* Add address */
                $this->insertAddress($page, $shipment->getStore());
                /* Add head */
                $this->insertOrder(
                    $page,
                    $shipment,
                    $this->_scopeConfig->isSetFlag(
                        self::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $order->getStoreId()
                    )
                );
                /* Add document text and number */
                $this->insertDocumentNumber($page, __('Packing Slip # ') . $shipment->getIncrementId());

                /* Add table */
                $this->_drawHeader($page);
                /* Add body */
                foreach ($shipment->getAllItems() as $item) {
                    if ($item->getOrderItem()->getParentItem()) {
                        continue;
                    }
                    /* Draw item */
                    $this->_drawItem($item, $page, $order);
                    $page = end($pdf->pages);
                }
            }else{
                $products = $this->getVendorData($shipment);
                $_locations = $this->_helper->getLocationsInfo($products);
                $this->y = $this->y ? $this->y : 815;
                $top = $this->y;
                $top += 10;
                if($_locations){
                    $page = $this->newPage();
                    $order = $shipment->getOrder();
                    /* Add image */
                    $this->insertLogo($page, $shipment->getStore());
                    /* Add address */
                    $this->insertAddress($page, $shipment->getStore());
                    /* Add head */
                    $this->insertOrder(
                        $page,
                        $shipment,
                        $this->_scopeConfig->isSetFlag(
                            self::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $order->getStoreId()
                        )
                    );
                    /* Add document text and number */
                    $this->insertDocumentNumber($page, __('Packing Slip # ') . $shipment->getIncrementId());
                }

                foreach($_locations as $locationKey => $_location) {
                    /* Add table */
                    $vendorDetails = $this->_productHelper->getVendor($_location->getVendorId());

                    $this->drawText($page,$vendorDetails);

                    $this->_drawHeader($page);

                    /* Add body */
                    foreach ($shipment->getAllItems() as $item) {
                        if ($item->getOrderItem() && $item->getOrderItem()->getParentItem()) {
                            continue;
                        }
                        /* Draw item */
                        $this->_drawItem($item, $page, $order);
                        $page = end($pdf->pages);
                    }
                }
            }

            if ($shipment->getStoreId()) {
                $this->_localeResolver->revert();
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }



    /**
     * draw notice below content
     *
     * @param \Zend_Pdf_Page $page
     */
    protected function drawText(\Zend_Pdf_Page $page,$vendorDetails) {
        $iFontSize = 10;     // font size
        $iColumnWidth = 520; // whole page width
        $iWidthBorder = 400; // half page width
        $soldBy = "SOLD BY: "; // your message
        $vendorName = 'Vendor Name: ' . $vendorDetails->getName();
        $vendorAdress = 'Vendor Address: ' . $vendorDetails->getAddress();
        $vendorPhone = 'Vendor Contact Number: ' . $vendorDetails->getPhone();
        $iXCoordinateText = 30;
        $sEncoding = 'UTF-8';
        $this->y -= 10; // move down on page
        try {
            $oFont = $this->_setFontRegular($page, $iFontSize);

            $page->setFillColor(new \Zend_Pdf_Color_RGB(0, 0, 0));
            $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0));
            $iXCoordinateBorder = 25;
            // draw top border
            $page->drawLine($iXCoordinateBorder, $this->y, $iXCoordinateBorder + $iWidthBorder, $this->y);
            // draw text
            $this->y -= 15;
            $page->drawText($soldBy, $iXCoordinateText, $this->y, $sEncoding);
            $this->y -= 15;
            $page->drawText($vendorName, $iXCoordinateText, $this->y, $sEncoding);
            $this->y -= 15;
            $page->drawText($vendorAdress, $iXCoordinateText, $this->y, $sEncoding);
            $this->y -= 15;
            $page->drawText($vendorPhone, $iXCoordinateText, $this->y, $sEncoding);
            $this->y -= 15;
            // draw bottom border
            $page->drawLine($iXCoordinateBorder, $this->y, $iXCoordinateBorder + $iWidthBorder, $this->y);
            // draw left border
            $page->drawLine($iXCoordinateBorder, $this->y, $iXCoordinateBorder, $this->y + 75);
            // draw right border
            $page->drawLine($iXCoordinateBorder + $iWidthBorder, $this->y, $iXCoordinateBorder + $iWidthBorder, $this->y + 75);
            $this->y -= 15;
        } catch (\Exception $exception) {
            // handle
        }
    }



    public function getVendorData($shipment){
        $products = $shipment->getAllItems();
        foreach ($products as $product){
            $vendorId = $this->getVendorIdByProductId($product->getProductId());
            if($vendorId){
                $locationId = $this->getLocationByProductId($vendorId);
                $product->setData('location_id',$locationId);
            }
            $product->setData('vendor_id',$vendorId);
        }
        return $products;
    }

    public function getVendorIdByProductId($id){
        $vendor = $this->_vendorFactory->create();
        $vendorId = $vendor->getResource()->getVendorIdByProductId($id);
        if (empty($vendorId)) {
            return false;
        }
        return $vendorId;
    }

    public function getLocationByProductId($vendorId)
    {
        $conn = $this->_resource->getConnection();

        $table = $conn->getTableName('omnyfy_vendor_location_entity');

        $select = $conn->select()->from(
            $table,
            ['entity_id']
        )
            ->where(
                "vendor_id = ?",
                $vendorId
            )
            ->limit(1)
        ;

        return $conn->fetchOne($select);
    }
}
