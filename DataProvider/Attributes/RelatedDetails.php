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

class RelatedDetails extends MagentoDataProvider
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
        if (isset($datarequests["attribute_id"]) and isset($datarequests["related_attribute_id"])){
            $attribute_id=$datarequests["attribute_id"];
            $related_attribute_id=$datarequests["related_attribute_id"];
            $select="SELECT option_id as attribute_id_option, 
                            related_option_id as related_attribute_id_option,
                            CASE
                                WHEN related_option_id > 0 THEN 1
                                ELSE 0
                            END AS is_mapped
                    FROM eav_attribute_option 
                    WHERE attribute_id='$attribute_id'
                    ORDER BY sort_order ASC";
            $lastupdate=$this->giuseppecore->getTablesLastUpdate("eav_attribute_option");
            $data=$this->giuseppecore->getDataProvidersData($select,$datarequests,$lastupdate);

            //prendo le opzioni degli attributi
            $emptyvalues=["value"=>0,"label"=>"NON MAPPATA"];
            $attributeoptions=[];
            $attributeoptions["labels"]=[];
            $attributeoptions["options"]["attribute_id_option"]=[];
            //$attributeoptions["options"]["attribute_id_option"][]=$emptyvalues;
            $attributeoptions["options"]["related_attribute_id_option"]=[];
            $attributeoptions["options"]["related_attribute_id_option"][]=$emptyvalues;
            $listattributes=$attribute_id.",".$related_attribute_id;
            $select="SELECT DISTINCT 
                    eav_attribute_option.option_id as value, 
                    eav_attribute_option.sort_order as sort_order, 
                    eav_attribute_option.attribute_id as attribute_id, 
                    eav_attribute_option_value.value as label, 
                    eav_attribute_option_value.store_id as store_id
                FROM eav_attribute_option 
                    LEFT OUTER JOIN eav_attribute_option_value 
                        ON eav_attribute_option.option_id = eav_attribute_option_value.option_id
                WHERE eav_attribute_option_value.store_id = 0 
                AND eav_attribute_option.attribute_id IN (".$listattributes.")
                ORDER BY eav_attribute_option.sort_order ASC, eav_attribute_option.attribute_id";
            $relatedoptions=$this->connection->fetchAll($select);
            foreach ($relatedoptions as $relop){
                $key="attribute_id_option";
                $attribute_id_parsed=$relop["attribute_id"];
                if ($attribute_id_parsed==$related_attribute_id){
                    $key="related_attribute_id_option";
                }
                $attributeoptions["options"][$key][]=[
                    "value"=>$relop["value"],
                    "label"=>$relop["label"]
                ];
            }
            $select="SELECT attribute_id,frontend_label
                        FROM eav_attribute
                            WHERE attribute_id IN (".$listattributes.")";
            $relatedlabels=$this->connection->fetchAll($select);
            foreach ($relatedlabels as $relatedlabel){
                $key="attribute_id_option";
                $attribute_id_parsed=$relatedlabel["attribute_id"];
                if ($attribute_id_parsed==$related_attribute_id){
                    $key="related_attribute_id_option";
                }
                $attributeoptions["labels"][$key]=$relatedlabel["frontend_label"];
            }
            $data["settings"]=$attributeoptions;
            

        }
        //var_dump($data);exit;
        return $data;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }
}

?>


    