<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace condor872\SearchableAttributes\Plugin\Block\Adminhtml\Product\Attribute\Edit;
use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tabs as AttributeEditTabs;

class Tabs
{
    /**
     * @param AttributeEditTabs $subject
     * @return array
     */
    public function beforeToHtml(AttributeEditTabs $subject)
    {
        $attribute_id=$this->checkifcanshow();
        if (!$attribute_id){
            return [];
        }
        $content=$this->checkifattributehasparent($attribute_id);
        if (!$content){
            $content = $subject->getChildHtml('related');
        }
        $subject->addTabAfter(
            'related',
            [
                'label' => __('Attributi Correlati'),
                'title' => __('Attributi Correlati'),
                'content' => $content,
            ],
            'front'
        );
        return [];
    }
    public function checkifcanshow(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $Registry = $objectManager->get(\Magento\Framework\Registry::class);
        $attributeshowarray=["select","multiselect"];
        $attributeObject = $Registry->registry('entity_attribute');
		$attributedata=$attributeObject->getData();
        $attribute_id=false;
        if (isset($attributedata["attribute_id"])){
            $attribute_id=$attributedata["attribute_id"];
            if ($attribute_id>0){
                $frontend_input=$attributedata["frontend_input"];
                if (in_array($frontend_input, $attributeshowarray)) {
                    return $attribute_id;
                }
            }
        }
        return false;
    }
    public function checkifattributehasparent($attribute_id){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_resourceConnection=$objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $this->_backendUrl=$objectManager->get(\Magento\Backend\Model\UrlInterface::class);
        $connection = $this->_resourceConnection->getConnection();
        $select="SELECT 
                    catalog_eav_attribute.attribute_id as attribute_id,
                    eav_attribute.frontend_label as label
                    FROM catalog_eav_attribute
                    LEFT OUTER JOIN eav_attribute 
                        ON catalog_eav_attribute.attribute_id = eav_attribute.attribute_id
                    WHERE catalog_eav_attribute.related_attribute_id = '$attribute_id'";
        $relatedattribute=$connection->fetchAll($select);
        if (count($relatedattribute)==0){return false;}
        $relatedattribute=$relatedattribute[0];
        $related_attribute_id=$relatedattribute["attribute_id"];
        $related_attribute_label=$relatedattribute["label"];
        $attribute_url=$this->_backendUrl->getUrl("catalog/product_attribute/edit/attribute_id/".$related_attribute_id);
        $html="";
        $html.="Questo attributo dipende dall'attributo <b>".$related_attribute_label."</b><br>";
        $html.="<a target='_blank' href='".$attribute_url."'>Vedi</a>";
        return $html;
    }
}