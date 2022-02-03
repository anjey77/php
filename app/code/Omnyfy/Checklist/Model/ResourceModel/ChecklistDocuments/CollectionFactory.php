<?php
namespace Omnyfy\Checklist\Model\ResourceModel\ChecklistDocuments;

class CollectionFactory
{

    protected $_objectManager = null;
    protected $_instanceName = null;
    protected $_resource;
    protected $_connection;
    protected $_table;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ResourceConnection $resources,
        $instanceName = '\\Omnyfy\\Checklist\\Model\\ResourceModel\\ChecklistDocuments\\Collection')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName  = $instanceName;
        $this->_resource      = $resources;
        $this->_connection    = $resources->getConnection();
        $this->_table         = $this->_resource->getTableName('omnyfy_checklist_checklistdocuments');
    }

    public function create(array $data = array())
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }

    public function saveOptions(array $data = array()) {
        $columns = implode(", ",array_keys($data));
        $escaped_values = array_values($data);
        $values  = "'".implode("', '", $escaped_values)."'";
        $sql = "INSERT INTO ".$this->_table."($columns) VALUES ($values)";
        $this->_connection->query($sql);
        return true;
    }

    public function deleteOption($id) {
        try {
            $sql = "DELETE FROM " . $this->_table . " WHERE checklistdocument_id =" . $id . ";";
            $this->_connection->query($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateOptions($id, $data) {
        $sql = "UPDATE " . $this->_table." SET ";
        foreach($data as $key=>$column) {
            $sql.= $key ." = '".$column."',";
        }
        $sql = trim($sql,',');
        $sql.= " WHERE checklistdocument_id =" . $id . ";";
        $this->_connection->query($sql);
        return true;
    }
}
