<?php
namespace condor872\SearchableAttributes\DataProvider\Attributes;

class Related extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $employeeCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_resourceConnection = $resourceConnection;
        $this->_backendUrl = $backendUrl;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        //$key=$this->request->getParam("key");
        $attributeObject = $this->_coreRegistry->registry('entity_attribute');
		$attributedata=$attributeObject->getData();
        $attribute_id=$attributedata["attribute_id"];
        $disablerelations=false;
        $viewgrid=false;
        $linktorelated="";
        if ($attributedata["related_attribute_id"]>0){
            $disablerelations=true;
            $viewgrid=true;
            $linktorelated=$this->_backendUrl->getUrl("catalog/product_attribute/edit/attribute_id/".$attributedata["related_attribute_id"]);
            $linktorelated="<a target='_blank' href='".$linktorelated."'><b>Vedi Attributo Correlato</b></a>";
        }
        $data=[];
        $optionsdata=$this->getRelatableattributes($attribute_id);
        $attributedata["settings"]=[];
        $attributedata["settings"]["related_attribute_id"]["disabled"]=$disablerelations;
        $attributedata["settings"]["related_attribute_id"]["options"]=$optionsdata;
        $attributedata["settings"]["related_attribute_id"]["additionalInfo"]=$linktorelated;
        $attributedata["settings"]["grid_related"]["visibility"]=$viewgrid;
        $data["items"][]=$attributedata;
        return $data;
        
        
    }
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }
    public function getRelatableattributes($attribute_id){
        $attributes=[];
        $connection = $this->_resourceConnection->getConnection();
        $select="SELECT 
                attribute_id as value,
                frontend_label as label
            FROM eav_attribute
            WHERE attribute_id IN(
                SELECT attribute_id FROM catalog_eav_attribute 
                    WHERE related_attribute_id IS NULL 
                    OR related_attribute_id=0
            )
            AND attribute_id IN (
                SELECT DISTINCT attribute_id FROM eav_entity_attribute
                WHERE attribute_set_id IN (
                    SELECT DISTINCT attribute_set_id FROM eav_entity_attribute
                        WHERE attribute_id='$attribute_id'
                )
            )
            AND frontend_label!=''
            AND attribute_id!='$attribute_id'
            AND frontend_input IN ('select','multiselect')
            AND is_user_defined=1
            AND (
					source_model IS NULL 
				    OR source_model='Magento\\\Eav\\\Model\\\Entity\\\Attribute\\\Source\\\Table'
				)";
        $options=$connection->fetchAll($select);
        $attributesisd=implode(",",array_column($options, 'value'));
        $attributeslabels=array_column($options, 'label', 'value');
        return $options;
    }
    public function checkifattributehasparent($attribute_id){
        $connection = $this->_resourceConnection->getConnection();
        $select="SELECT 
                    catalog_eav_attribute.attribute_id as attribute_id
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
        $html.="Questo attributo dipende dall'attributo ".$related_attribute_label."<br>";
        $html.="<a target='_blank' href='".$attribute_url."'>Vedi</a>";
        return $html;
    }
}