<?php
namespace condor872\SearchableAttributes\Controller\Adminhtml\Saveattributeoptions;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
 
class InlineEdit extends Action
{
    protected $jsonFactory;
 
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        \condor872\Core\Helper\Core $giuseppecore,
        \Magento\Framework\App\ResourceConnection $ResourceConnection
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->ResourceConnection = $ResourceConnection;
        $this->giuseppecore = $giuseppecore;
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
                    $datawithoutnewoptions=$this->addOption($postItems);
                    //$messages[]=json_encode($datawithoutnewoptions);
                    if (!empty($datawithoutnewoptions)){
                        $new_update=$this->FixNewData($datawithoutnewoptions);
                        if (count($new_update)>0){
                            $this->connection = $this->ResourceConnection->getConnection();
                            $this->connection->insertOnDuplicate("eav_attribute_option_value", $new_update);
                        }
                    }
                    //$new_update=$this->FixNewData($datawithoutnewoptions);
                    //$messages[]=json_encode($datawithoutnewoptions);
                    $error=false;/*
                    $messages[]="Record Salvati Correttamente";
                    $error=false;/*
                foreach ($postItems as $entityId) {
                    $newarrayupdate=[];
                    $newarrayupdate["option_id"]=$entityId["attribute_id_option"];
                    $newarrayupdate["related_option_id"]=$entityId["related_attribute_id_option"];
                    $arrayupdate[]=$newarrayupdate;
                }
                $connection=$this->ResourceConnection->getConnection();
                $connection->insertOnDuplicate("eav_attribute_option", $arrayupdate);
                $messages[]="Opzioni Aggiornate Correttamente";
                $error=false;*/
            }
        }
 
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    public function addOption($optionarray){
        $newoptions=[];
        foreach ($optionarray as $key=>$options){
            $is_new_option=(int)$options["is_new_option"];
            if ($is_new_option>0){
                $newoptions[]=$options;
                unset($optionarray[$key]);
            }
        }
        $insertoptions=[];
        if (!empty($newoptions)){
            $insertoptions=[];
            foreach ($newoptions as $options){
                $attribute_id=$options["attribute_id"];
                $default_label=trim($options["store_0"]);
                unset($options["attribute_id"]);
                if ($default_label==""){continue;}
                $linerecord=[];
                foreach ($options as $store_id_key=>$value){                    
                    if (!str_contains($store_id_key, 'store_')){continue;}
                    $store_id=(int)explode("_",$store_id_key)[1];
                    $value=trim($value);
                    if ($value==""){continue;}
                    $linerecord[$store_id]=$value;
                }
                $insertoptions[$attribute_id][$default_label]=$linerecord;
            }
        }
        if (!empty($insertoptions)){
            foreach ($insertoptions as $attribute_id=>$options){
                $this->giuseppecore->AddAttributeOptions($attribute_id,$options);
            }
        }
        return $optionarray;

    }
    public function getOldData($optionspassed){
        $options_string=implode(",",$optionspassed);
        $select="SELECT value_id,
                     option_id,
                     store_id,
                     value
                    FROM eav_attribute_option_value WHERE option_id IN ($options_string)";
        $this->connection = $this->ResourceConnection->getConnection();
        $attributes_options=$this->connection->fetchAll($select);
        $oldOptions=[];
        foreach ($attributes_options as $option){
            $store_id="store_".$option["store_id"];
            $option_id=$option["option_id"];
            $oldOptions[$option_id."_".$store_id]=$option;
        }
        return $oldOptions;
    }

    public function FixNewData($data){
        $optionspassed=array_column($data,"option_id");
        $oldOptions=$this->getOldData($optionspassed);
        $default_values=array_column($data,"store_0", "option_id");
        $relatedoptions=[];
        foreach ($data as $option) {
            $option_id=$option["option_id"];
            $attribute_id=$option["attribute_id"];
            if (isset($option["related_option_id"])){
                $related_option_id=$option["related_option_id"];
                $relatedoptions[$option_id]=[
                    "option_id"=>$option_id,
                    "attribute_id"=>$attribute_id,
                    "related_option_id"=>$related_option_id
                ];
            }
            unset($option["option_id"]);
            foreach ($option as $store_id_key=>$value){
                if (!str_contains($store_id_key, 'store_')){continue;}
                $store_id=explode("_",$store_id_key)[1];
                $key_search=$option_id."_".$store_id_key;
                $defaultvalue=trim($default_values[$option_id]);
                $value=trim($value);
                if ($value==$defaultvalue){
                    if (!isset($oldOptions[$key_search])){
                        continue;
                    }                    
                }
                if (isset($oldOptions[$key_search])){
                    $oldOptions[$key_search]["value"]=$value;
                }
                else{
                    $linearray=[
                      "value_id"=>null,
                      "option_id"=>$option_id,
                      "store_id"=>$store_id,
                      "value"=>$value
                    ];
                    $oldOptions[$key_search]=$linearray;
                }
            }
        }
        $newoptions=[];
        foreach ($oldOptions as $option) {
            $newoptions[]=$option;
        }

        if (!empty($relatedoptions)){
            $this->connection = $this->ResourceConnection->getConnection();
            $this->connection->insertOnDuplicate("eav_attribute_option", $relatedoptions);
        }
        
        return $newoptions;

    }
}