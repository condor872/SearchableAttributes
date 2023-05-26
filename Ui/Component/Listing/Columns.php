<?php
namespace condor872\SearchableAttributes\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use condor872\SearchableAttributes\Ui\Component\Listing\Column\CustomColumnCreator;
use Magento\Framework\App\ResourceConnection;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var CustomColumnCreator
     */
    private $columnCreator;

    public function __construct(
        ContextInterface $context,
        CustomColumnCreator $columnCreator,
        ResourceConnection $ResourceConnection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->columnCreator = $columnCreator;
        $this->ResourceConnection = $ResourceConnection;
        $this->connection = $this->ResourceConnection->getConnection();
    }

    public function prepare()
    {
        
            $billingNameColumn = $this->components['store_0'];

            $stores=$this->getStores();
            foreach ($stores as $store){
                $column = $this->columnCreator->addColumnFromExistingColumn(
                    $billingNameColumn,
                    $store["colname"],
                    $store["colabel"],
                    $store["sort_order"],
                );
                $this->addComponent($store["colname"], $column);
            }

        parent::prepare();
    }

    public function getStores(){
        $storesdata=[];
        $select="SELECT CONCAT('store','_', store_id) as colname, name as colabel, sort_order FROM store WHERE is_active=1 AND store_id>0 ORDER BY sort_order ASC";
        $stores=$this->connection->fetchAll($select);
        return $stores;    

    }


}
