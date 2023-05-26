<?php

namespace condor872\SearchableAttributes\Controller\Adminhtml\Searchoptions;

use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Controller to search product for ui-select component
 */
class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
        /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultFactory
     * @param \Magento\Catalog\Model\ProductLink\Search $productSearch
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultFactory,
        \Magento\Framework\App\ResourceConnection $ResourceConnection,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultJsonFactory = $resultFactory;
        $this->ResourceConnection = $ResourceConnection;
        parent::__construct($context);
    }

    /**
     * Execute product search.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() : \Magento\Framework\Controller\ResultInterface
    {
        $attribute_id = (int)$this->getRequest()->getParam('attribute_id');

        $connection = $this->ResourceConnection->getConnection();
        //prendo gli id delle labels
        $select="SELECT * FROM eav_attribute_option_value 
            WHERE store_id=0
            and option_id IN (SELECT option_id FROM eav_attribute_option WHERE attribute_id=".$attribute_id." ORDER BY sort_order ASC)";
        $labels=$connection->fetchAll($select);

        $productById = [];
        /** @var  ProductInterface $product */
        foreach ($labels as $label) {
            $option_id = $label["option_id"];
            $productById[] = [
                'value' => $option_id,
                'label' => $label["value"],
                'path' => '',
                'level' => 0,
                '__disableTmpl' => true
                //'is_active' => 1
            ];
        }
        $relationsattribute=[];
        $therearerelations=false;
        $queryrelated="SELECT 
                            option_id, 
                            related_option_id 
                        FROM eav_attribute_option
                        WHERE related_option_id>0 AND attribute_id='$attribute_id'";
        $relations=$connection->fetchAll($queryrelated);
        foreach ($relations as $related){
            $relationsattribute[$related["option_id"]]=$related["related_option_id"];
            $therearerelations=true;
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'options' => $productById,
            'related_options' => $relationsattribute,
            'therearerelations' => $therearerelations
        ]);
    }
}