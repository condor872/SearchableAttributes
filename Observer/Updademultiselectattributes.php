<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace condor872\SearchableAttributes\Observer;

class Updademultiselectattributes implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
	public function __construct(
		\Magento\Framework\App\ResourceConnection $resource
    ) {
		
		$this->resource = $resource;
		$this->connection = $this->resource->getConnection();
    } 
	 
	 
	
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $_product = $observer->getProduct();
        /*echo json_encode($_product->getData());
        exit;
        
        $array = (array) $_product;
        $out=[];
        if ($_product->getData("multiselectattributes")){
            $attributestocheck=explode(",",$_product->getData("multiselectattributes"));
            foreach ($attributestocheck as $attribute){
                if ($_product->getData($attribute)){
                    $attribute_data=$_product->getData($attribute);
                    $out[$attribute]=$attribute_data;
                    $newattributedata=[];
                    foreach ($attribute_data as $value){
                        if ((int)$value!=0){
                            $newattributedata[]=$value;
                        }
                    }
                    if (empty($newattributedata)){
                        $newattributedata="";
                    }
                    $_product->setData($attribute,$newattributedata);
                }
            }
        }*/
        //echo (json_encode($out));exit;
    }
	
	
}
