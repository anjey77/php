<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Gdpr
 */


namespace Amasty\Gdpr\Controller\Customer;

use Amasty\Gdpr\Block\Settings as BlockSettings;
use Amasty\Gdpr\Model\CustomerData;
use Amasty\Gdpr\Controller\Result\CsvFactory;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Customer\Model\Authentication;
use Amasty\Gdpr\Model\Config;

class DownloadCsv extends Action
{
    const CSV_FILE_NAME = 'personal-data.csv';
    const ORDER_INCREMENT_ID_REQUEST_KEY_NAME = 'order_increment_id';

    /**
     * @var CustomerData
     */
    private $customerData;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var Config
     */
    private $configProvider;

    /**
     * @var CsvFactory
     */
    private $csvFactory;

    public function __construct(
        Context $context,
        CustomerData $customerData,
        Session $customerSession,
        LoggerInterface $logger,
        File $fileDriver,
        Authentication $authentication,
        FormKeyValidator $formKeyValidator,
        Config $configProvider,
        CsvFactory $csvFactory
    ) {
        parent::__construct($context);
        $this->customerData = $customerData;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->fileDriver = $fileDriver;
        $this->formKeyValidator = $formKeyValidator;
        $this->authentication = $authentication;
        $this->configProvider = $configProvider;
        $this->csvFactory = $csvFactory;
    }

    public function execute()
    {
        $errorMessage = '';

        if (!$this->configProvider->isAllowed(Config::DOWNLOAD)) {
            $errorMessage = __('Access denied.');
        }

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $errorMessage = __('Invalid Form Key. Please refresh the page.');
        }

        if ($errorMessage) {
            $this->messageManager->addErrorMessage($errorMessage);
            $this->_redirect('*/*/settings');
            return;
        }

        $customerId = (int)$this->customerSession->getCustomerId();
        $customerPass = $this->getRequest()->getParam('current_password');

        try {
            if ($customerId) {
                $this->authentication->authenticate($customerId, $customerPass);
            }
        } catch (\Magento\Framework\Exception\AuthenticationException $e) {
            $this->messageManager->addErrorMessage(__('Wrong Password. Please recheck it.'));
            $this->_redirect('*/*/settings');
            return;
        }

        $data = (bool)$this->getRequest()->getParam(BlockSettings::IS_ORDER_LAYOUT_VARIABLE_NAME)
            ? $this->customerData->getGuestPersonalData($this->getOrderIncrementId())
            : $this->customerData->getPersonalData($customerId);

        $response = $this->csvFactory->create(['fileName' => self::CSV_FILE_NAME]);
        $response->setData($data);

        return $response;
    }

    /**
     * @return string|int
     */
    private function getOrderIncrementId()
    {
        return $this->getRequest()->getParam(self::ORDER_INCREMENT_ID_REQUEST_KEY_NAME);
    }
}
