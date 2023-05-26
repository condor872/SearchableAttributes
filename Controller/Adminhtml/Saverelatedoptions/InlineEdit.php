<?php

namespace condor872\SearchableAttributes\Controller\Adminhtml\Saverelatedoptions;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
 
class InlineEdit extends Action
{
    protected $jsonFactory;
 
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        \Magento\Framework\App\ResourceConnection $ResourceConnection
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->ResourceConnection = $ResourceConnection;
    }
 
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $arrayupdate=[];
        $error = false;
        $messages = [];
        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items');
            if (empty($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach ($postItems as $entityId) {
                    $newarrayupdate=[];
                    $newarrayupdate["option_id"]=$entityId["attribute_id_option"];
                    $newarrayupdate["related_option_id"]=$entityId["related_attribute_id_option"];
                    $arrayupdate[]=$newarrayupdate;
                }
                $connection=$this->ResourceConnection->getConnection();
                $connection->insertOnDuplicate("eav_attribute_option", $arrayupdate);
                $messages[]="Opzioni Aggiornate Correttamente";
                $error=false;
            }
        }
 
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}