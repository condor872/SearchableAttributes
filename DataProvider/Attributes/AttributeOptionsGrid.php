<?php
namespace condor872\SearchableAttributes\DataProvider\Attributes;


use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as MagentoDataProvider;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;

class AttributeOptionsGrid extends MagentoDataProvider
{
    /**
     * @var \Magento\Ui\DataProvider\AddFieldToCollectionInterface[]
     */
    protected $addFieldStrategies;

    /**
     * @var \Magento\Ui\DataProvider\AddFilterToCollectionInterface[]
     */
    protected $addFilterStrategies;

    /**
     * @var Http
     */
    protected $request;

    /**
     * OrderDocumentsProvider constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        ResourceConnection $ResourceConnection,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \condor872\Core\Helper\Core $giuseppecore,
        array $meta = [],
        array $data = []
    )
    {
        $this->giuseppecore = $giuseppecore;
        $this->request = $request;
        $this->data = $data;
        $this->ResourceConnection = $ResourceConnection;
        $this->_backendUrl = $backendUrl;
        $this->connection = $this->ResourceConnection->getConnection();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reporting, $searchCriteriaBuilder, $request, $filterBuilder, $meta, $data);
    }
    
    public function getData()
    {
        $data=[];      
        $data["items"]=[];
        $datarequests=$this->request->getParams();
        $newoptionsenabled=false;
        if (isset($datarequests["newoptionsenabled"])){
            $newoptionsenabled=$datarequests["newoptionsenabled"];
            if (!isset($datarequests["attribute_id"])){
                $newoptionsenabled=false; 
            }
            unset($datarequests["newoptionsenabled"]);
        }
        $lastupdate=$this->giuseppecore->getTablesLastUpdate(["store","eav_attribute_option_value","eav_attribute_option","eav_attribute","eav_entity_type"]);
        $data=$this->giuseppecore->getDataProvidersData($this->getQuery(),$datarequests,$lastupdate);
        $data["settings"]["options"]["attributes"]=$this->getAttributesList();
        $related_attribute_options_settings=[];
        $related_attribute_options_settings["options"]=[["value"=>'0',"label"=>"NON MAPPATO"]];
        $related_attribute_options_settings["visible"]=false;
        foreach ($data["items"] as $key=>$row){
            /*$row["linkattribute"]="Vedi";
            $row["link"]="catalog/product_attribute/edit/attribute_id/".$row["attribute_id"];*/
            $row["is_new_option"]=0;
            $data["items"][$key]=$row;
        }
        if ($newoptionsenabled){
            $rowdata=$this->getEditRow($datarequests["attribute_id"],1);
            foreach ($rowdata as $linerow){
                array_unshift($data["items"], $linerow);
            }
            $relatedoptions=$this->getRelatedOptions($datarequests["attribute_id"]);
            if ($relatedoptions){
                foreach ($relatedoptions as $option){
                    array_push($related_attribute_options_settings["options"], $option);
                    $related_attribute_options_settings["visible"]=true;
                    
                }
            }
        }
        $data["settings"]["options"]["related_options_settings"]=$related_attribute_options_settings;
        return $data;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }

    public function getQuery(){
        $upperquery=[];
        $upperquery[]="SELECT tab.attribute_id";
        $upperquery[]="tab.option_id";
        $mainquery=[];
        $select="SELECT store_id, name FROM store WHERE is_active=1 ORDER BY sort_order ASC";
        $stores=$this->connection->fetchAll($select);
        foreach ($stores as $store){
            $upperquery[]="CASE WHEN tab.store_".$store["store_id"]." IS NULL THEN tab.store_0 ELSE tab.store_".$store["store_id"]." END AS store_".$store["store_id"];
            $mainquery[]="MAX(CASE WHEN eav_attribute_option_value.store_id = ".$store["store_id"]." 
                                THEN eav_attribute_option_value.value
                                    END) AS store_".$store["store_id"];
        }
        $finalquery="";
        $finalquery.=implode(" , ",$upperquery)." , ";
        $finalquery.="tab.related_option_id";
        $finalquery.=" FROM ( ";
        $finalquery.="SELECT 
                        eav_attribute_option.attribute_id,
                        eav_attribute_option.option_id, 
                        eav_attribute_option.related_option_id, ";
        $finalquery.=implode(" , ",$mainquery);
        $finalquery.=" FROM eav_attribute_option 
                            LEFT OUTER JOIN eav_attribute_option_value 
                                ON eav_attribute_option.option_id = eav_attribute_option_value.option_id
                                WHERE eav_attribute_option.attribute_id IN (
                                    SELECT attribute_id FROM eav_attribute 
                                        WHERE frontend_input IN ('select','multiselect')
                                        AND is_user_defined=1
                                        AND entity_type_id=(SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code='catalog_product') 
                                )
                            GROUP BY option_id
                            ORDER BY eav_attribute_option.sort_order ASC";
        $finalquery.=" ) tab";
        return $finalquery;
    }
    public function getAttributesList(){
        $select="SELECT 
                    eav_attribute.attribute_id as value, 
                    eav_attribute.frontend_label as label 
                FROM eav_attribute 
                WHERE frontend_input 
                IN('select','multiselect') 
                AND is_user_defined=1 
                AND entity_type_id=(SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code='catalog_product') 
                ORDER BY eav_attribute.frontend_label ASC";
        return $this->connection->fetchAll($select);
    }
    public function getEditRow($attribute_id,$numrows=1){
        $editrow=[];
        $editrow["option_id"]="";
        $editrow["attribute_id"]=$attribute_id;
        //$editrow["linkattribute"]="Vedi";
        //$editrow["link"]="catalog/product_attribute/edit/attribute_id/".$attribute_id;
        $editrow["is_new_option"]=1;
        $editrow["related_option_id"]='0';
        $select="SELECT store_id FROM store WHERE is_active=1 ORDER BY sort_order ASC";
        $stores=$this->connection->fetchAll($select);
        foreach ($stores as $store){
            $editrow["store_".$store["store_id"]]="";
        }

        $records=1;
        $returnrow=[];
        while($records <= $numrows) {
            $editrow["option_id"]=$attribute_id."_".$records;
            $returnrow[]=$editrow;
            $records++;
          }
        return $returnrow;
    }

    public function getRelatedOptions($main_attribute_id){
        $related_options=false;
        $select="SELECT related_attribute_id FROM catalog_eav_attribute WHERE attribute_id='$main_attribute_id'";
        $related_attribute_id=$this->connection->fetchOne($select);
        if ($related_attribute_id){
            $select="SELECT 
                        eav_attribute_option.option_id AS value,
                        eav_attribute_option_value.value as label
                    FROM eav_attribute_option
                    LEFT OUTER JOIN eav_attribute_option_value 
                                ON eav_attribute_option.option_id = eav_attribute_option_value.option_id
                    WHERE eav_attribute_option.attribute_id='$related_attribute_id'
                    AND eav_attribute_option_value.store_id = 0
                    ORDER BY eav_attribute_option.sort_order ASC";
            $related_options=$this->connection->fetchAll($select);
        }
        return $related_options;
    }
}

?>