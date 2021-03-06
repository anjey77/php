<?php
/**
 * Created by PhpStorm.
 * User: Sanjaya-offline
 * Date: 31/07/2020
 * Time: 3:42 PM
 */

namespace Omnyfy\Vendor\Controller\Adminhtml\Vendor\Set;


class Delete  extends \Omnyfy\Vendor\Controller\Adminhtml\Vendor\Set
{
    public function execute()
    {
        try {
            $entityTypeId = $this->_getEntityTypeId();
            $attributeSetId = $this->getRequest()->getParam('id', false);
            /* @var $model \Magento\Eav\Model\Entity\Attribute\Set */
            $model = $this->_objectManager->create('Magento\Eav\Model\Entity\Attribute\Set')
                ->setEntityTypeId($entityTypeId);

            if ($attributeSetId) {
                $model->load($attributeSetId);
                $model->delete();
            }

            $this->messageManager->addSuccessMessage(__('Deleted the attribute set successfully'));
        } catch (\Exception $exception){
            $this->messageManager->addErrorMessage(__('Error deleting the attribute: '.$exception->getMessage()));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('omnyfy_vendor/*');
        return $resultRedirect;
    }

    /**
     * Retrieve catalog product entity type id
     *
     * @return int
     */
    protected function _getEntityTypeId()
    {
        if ($this->_coreRegistry->registry('entityType') === null) {
            $this->_setTypeId();
        }
        return $this->_coreRegistry->registry('entityType');
    }
}