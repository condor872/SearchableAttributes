<?php 
namespace condor872\SearchableAttributes\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;


class Attributes extends AbstractModifier
{
    /**
     * @var Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager,
		\Magento\Framework\App\ResourceConnection $resourceConnection,
		\condor872\Core\Helper\Core $giuseppecore,
		\Magento\Backend\Model\UrlInterface $backendUrl
    ) {
        $this->arrayManager = $arrayManager;
		$this->_resourceConnection = $resourceConnection;
		$this->giuseppecore = $giuseppecore;
		$this->_backendUrl = $backendUrl;
    }

    /**
     * modifyData
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
		$data=$this->parsedata($data);

		return $data;
    }

    /**
     * modifyMeta
     *
     * @param array $data
     * @return array
     */
    public function modifyMeta(array $meta)
    {
		//echo (json_encode($this->updatemetasearch($meta)));exit;
		return $this->updatemetasearch($meta);
    }
	
	
	public function parsedata($data){
		$key=false;
		foreach ($data as $keyr=>$row){
			if (isset($row["product"])){$key=$keyr;break;}
		}
		if (!$key){
			$key="";
		}
		
		$connection = $this->_resourceConnection->getConnection();
		$optionsprovider=[];
		$select = "SELECT attribute_code, frontend_input, attribute_id 
						FROM eav_attribute 
							WHERE frontend_input IN ('select','multiselect')
							AND (
									source_model IS NULL 
									OR source_model='Magento\\\Eav\\\Model\\\Entity\\\Attribute\\\Source\\\Table'
								)";
		$presente=$connection->fetchAll($select);
		foreach ($presente as $attributecode)
					{	
						$attributecodearray=$attributecode["attribute_code"];
						$attrybutetype=$attributecode["frontend_input"];
						if ($attrybutetype=="multiselect"){
							if (isset($data[$key]["product"][$attributecodearray])){
								$nowvaluestring=$data[$key]["product"][$attributecodearray];
								$multiselectvalue=[];
								if ($nowvaluestring!=""){
									$multiselectvalue=explode(",",$nowvaluestring);
								}
								$data[$key]["product"][$attributecodearray."_multiselect"]=$multiselectvalue;
								//array_unshift($data[$key]["product"][$attributecodearray],"0");
								//array_push($arravalue, $data[$key]["product"][$attributecodearray]);
							}
						}
						$optionsprovider[$attributecode["attribute_id"]]=$attributecode["attribute_code"];
					}
		//passo i dati degli attributi correlati
		$select = "SELECT 
						eav_attribute.attribute_code as main_attribute_code,
						(SELECT 
							eav_attribute.attribute_code FROM eav_attribute 
								where eav_attribute.attribute_id = catalog_eav_attribute.related_attribute_id) 
									as child_attribute_code,
						eav_attribute.attribute_id as main_attribute_id,
						eav_attribute.frontend_label as main_attribute_label,
						(SELECT 
							eav_attribute.frontend_label FROM eav_attribute 
								where eav_attribute.attribute_id = catalog_eav_attribute.related_attribute_id) 
									as child_attribute_label,
						catalog_eav_attribute.related_attribute_id as child_attribute_id
					FROM eav_attribute
						LEFT OUTER JOIN catalog_eav_attribute 
							ON eav_attribute.attribute_id = catalog_eav_attribute.attribute_id
					WHERE catalog_eav_attribute.related_attribute_id>0";
		$correlazioni=$connection->fetchAll($select);
		$attributi_list=implode(",",array_column($correlazioni,"main_attribute_id"));

		//prendo le options
		$select = "SELECT DISTINCT option_id,
						attribute_id,
						related_option_id
					FROM eav_attribute_option
					WHERE attribute_id IN ($attributi_list)
					AND related_option_id>0";
		$opzioni=$connection->fetchAll($select);		
		$arrayopzioni=[];
		foreach ($opzioni as $opzione){
			$arrayopzioni[$opzione["attribute_id"]][$opzione["option_id"]]=$opzione["related_option_id"];
		}

		$arraydefinitivo=[];
		foreach ($correlazioni as $correlazione){
			$opzionicorrelate=[];
			if (isset($arrayopzioni[$correlazione["main_attribute_id"]])){
				$opzionicorrelate=$arrayopzioni[$correlazione["main_attribute_id"]];
			}
			$arraydefinitivo[$correlazione["main_attribute_code"]]=[
				"child_attribute_code"=>$correlazione["child_attribute_code"],
				"related_options"=>$opzionicorrelate
			];
		}
		$data[$key]["product"]["relatedoptions"]=$arraydefinitivo;

		//adesso metto le notices
		$labels=[];
		foreach ($correlazioni as $correlazione){
			$main_attribute_code=$correlazione["main_attribute_code"];
			$main_attribute_label=$correlazione["main_attribute_label"];
			$child_attribute_code=$correlazione["child_attribute_code"];
			$child_attribute_label=$correlazione["child_attribute_label"];
			$labels[$main_attribute_code]="Questo attributo Imposta automaticamente i valori per l'attributo ".$child_attribute_label;
			$labels[$child_attribute_code]="Questo attributo Eredita i valori dall'attributo ".$main_attribute_label;
		}
		$data[$key]["product"]["notices"]=$labels;
		//adesso prendo le options da passare come import ai select
		if (!empty($optionsprovider)){
			$provideoptions=[];
			$attributes_list=implode(",",array_keys($optionsprovider));
			$select = "SELECT 
							eav_attribute_option.attribute_id as attribute_id, 
							eav_attribute_option.option_id as value, 
							eav_attribute_option_value.value as label
						FROM eav_attribute_option 
							LEFT OUTER JOIN eav_attribute_option_value 
								ON eav_attribute_option.option_id = eav_attribute_option_value.option_id 
						WHERE eav_attribute_option_value.store_id = 0 
						AND eav_attribute_option.attribute_id IN ($attributes_list)
						ORDER BY eav_attribute_option.attribute_id ASC, eav_attribute_option.sort_order ASC";
			$valoriattributi=$connection->fetchAll($select);
			foreach ($valoriattributi as $options){
				$attribute_id=$options["attribute_id"];
				unset($options["attribute_id"]);
				$provideoptions[$attribute_id][]=$options;
			}
			foreach ($optionsprovider as $attribute_id=>$attribute_code){
				$optionsparsed=[];
				if (isset($provideoptions[$attribute_id])){
					$optionsparsed=$provideoptions[$attribute_id];
				}
				$data[$key]["product"]["customoptions"][$attribute_code]=$optionsparsed;
			}
		}	


		return $data;
		
    }
	
	public function updatemetasearch($metaarray)
		{
				$multiselectpassed=[];
				$attributesrelated=[];
				$url = $this->_backendUrl->getUrl("searchoptions/searchoptions/index");
				$urladdoption=$this->_backendUrl->getUrl("catalog/product_attribute/edit/attribute_id/idattributo");
				$connection = $this->_resourceConnection->getConnection();
				$select = "SELECT 
								eav_attribute.attribute_code, 
								eav_attribute.frontend_input, 
								eav_attribute.attribute_id,
								eav_attribute.frontend_label,
								catalog_eav_attribute.related_attribute_id,
								catalog_eav_attribute.make_searchable
									FROM eav_attribute
									LEFT OUTER JOIN catalog_eav_attribute 
										ON eav_attribute.attribute_id = catalog_eav_attribute.attribute_id
								WHERE eav_attribute.is_user_defined=1 
								AND eav_attribute.frontend_input IN ('select','multiselect')
								AND (
										eav_attribute.source_model IS NULL
										OR eav_attribute.source_model='Magento\\\Eav\\\Model\\\Entity\\\Attribute\\\Source\\\Table'
									)";
				$presente=$connection->fetchAll($select);
				
				foreach ($presente as $attributecode)
					{	
						$related_attribute_id=$attributecode["related_attribute_id"];
						$attributecodearray=$attributecode["attribute_code"];
						$attrybutetype=$attributecode["frontend_input"];
						$attribute_id=$attributecode["attribute_id"];
						$attribute_modal_label="Aggiungi Opzioni per l'attributo ".$attributecode["frontend_label"];
						$addoptionsurl = str_replace("idattributo", $attribute_id, $urladdoption);

						$updateoptions=[
							"callurl"=>$url,
							"attribute_id"=>$attribute_id,
							"target"=>$attributecodearray
						];

						$addhidden=false;
						$modifications=[];
						if ($attrybutetype=="select")
							{
								$modifications=[
									//'component' => 'Magento_Ui/js/form/element/ui-select',
									'component' => 'condor872_SearchableAttributes/js/form/product/uiselect',
									'elementTmpl' => 'ui/grid/filters/elements/ui-select',
									'filterOptions' => true,
									'showCheckbox' => true,
									'chipsEnabled' => true,
									'multiple' => false,
									'disableLabel' => true,
									'haschildrelated' => false,
									'relating_index' => $attributecodearray,
								];
								$modifications["imports"]["notice"]='product_form.product_form_data_source:data.product.notices.'.$attributecodearray;
								$modifications["imports"]["options"]='product_form.product_form_data_source:data.product.customoptions.'.$attributecodearray;
								if ($related_attribute_id>0){							
									$modifications["main_attribute_code"]=$attributecodearray;
									$modifications["haschildrelated"]=true;
									$modifications["imports"]["related_options"]='product_form.product_form_data_source:data.product.relatedoptions.'.$attributecodearray;
									/*$modifications["imports"]=[
										'related_options' => 'product_form.product_form_data_source:data.product.relatedoptions.'.$attributecodearray
									];*/
								}
							}
						if ($attrybutetype=="multiselect")
							{
								$updateoptions["target"]=$attributecodearray."_multiselect";
								$addhidden=$attributecodearray;
								$modifications=[
									//'component' => 'Magento_Ui/js/form/element/ui-select',
									'component' => 'condor872_SearchableAttributes/js/form/product/uiselect',
									'elementTmpl' => 'ui/grid/filters/elements/ui-select',
									'filterOptions' => true,
									'showCheckbox' => true,
									'chipsEnabled' => true,
									'multiple' => true,
									'disableLabel' => true,
									'haschildrelated' => false,
									'sortOrder' => 1,
									'relating_index' => $attributecodearray,
									'dataScope' => $attributecodearray."_multiselect",
									'options' => []
								];
								$modifications["imports"]["notice"]='product_form.product_form_data_source:data.product.notices.'.$attributecodearray;
								$modifications["imports"]["options"]='product_form.product_form_data_source:data.product.customoptions.'.$attributecodearray;
								if ($related_attribute_id>0){							
									$modifications["main_attribute_code"]=$attributecodearray;
									$modifications["haschildrelated"]=true;
									$modifications["imports"]["related_options"]='product_form.product_form_data_source:data.product.relatedoptions.'.$attributecodearray;
									/*$modifications["imports"]=[
										'related_options' => 'product_form.product_form_data_source:data.product.relatedoptions.'.$attributecodearray
									];*/
								}
							}

						

						if ($path = $this->arrayManager->findPath($attributecodearray, $metaarray)) {
							/*if ($attrybutetype=="multiselect"){
							$multiselectpassed[]=$attributecodearray;}
							*/
							$related_options_for_ui = $this->arrayManager->get(
								$path . '/arguments/data/config/options',
								$metaarray
							);
							$metaarray = $this->arrayManager->set(
								$path . '/arguments/data/config/options',
								$metaarray,
								[]
							);
							$metaarray = $this->arrayManager->merge(
								$path . '/arguments/data/config',
								$metaarray,
								$modifications
							);
							$containerpath = $this->arrayManager->findPath("container_".$attributecodearray, $metaarray);
							$container=$this->arrayManager->get(
								$containerpath,
								$metaarray
							);
							$container["arguments"]["data"]["config"]["breakLine"]=false;
							$container["arguments"]["data"]["config"]["label"]=false;
							$container["arguments"]["data"]["config"]["component"]="Magento_Ui/js/form/components/group";
							//$container["children"]["add_option_".$attributecodearray]=$this->createButton("Aggiungi Opzioni","add_option_".$attributecodearray,["redirect"=>$addoptionsurl]);
							$container["children"]["update_option_".$attributecodearray]=$this->createButton("Aggiorna Opzioni","update_option_".$attributecodearray,["updateOptions"=>$updateoptions]);
							$container["children"]["add_option_".$attributecodearray]=$this->createModalButton($attribute_id,$attribute_modal_label);
							
							if ($addhidden){
								$multiselectfield=$container["children"][$attributecodearray];
								$container["children"][$attributecodearray."_multiselect"]=$multiselectfield;
								$container["children"][$attributecodearray]=$this->addmultiselectfield($attributecodearray);
							}
							$metaarray = $this->arrayManager->set(
								$containerpath,
								$metaarray,
								$container
							);

							

						}
					}
			return $metaarray;
		}

		public function addmultiselectfield($attribute_code,/*$related_options_for_ui*/){
			$field=[
				'arguments' => [
					'data' => [
						'config' => [
							'visible' => false,
							'componentType' => 'field',
							'formElement'   => 'hidden',
							'dataType'      => 'text',
							'sortOrder' => 10000,
							'code' => $attribute_code,
							//'related_options_for_ui' => $related_options_for_ui,
							/*'exports' => [
								'related_options_for_ui' => '${ $.parentName}.'.$attribute_code.'_multiselect:options'
							]*/
						]
					]
				]
			];
			return $field;
		}

		public function addHiddenField($value){
			$field=[
                'giuseppe' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'visible' => false,
								'componentType' => 'fieldset',
								'dataScope' => 'data.product',
                            ],
                        ],
                    ],
                    'children' => [
						'multiselectattributes' => [
							'arguments' => [
								'data' => [
									'config' => [
										'componentType' => 'field',
										'formElement'   => 'input',
										'dataType'      => 'text',
										'default' => $value,
										'code'=>'multiselectattributes',
										'dataScope' => 'multiselectattributes'
									]
								]
							]
						]
					]
                ]
			];
			return $field;
		}
		public function createButton($fieldname,$scope,$url){
			$array=array (
				  'arguments' => 
				  array (
					'data' => 
					array (
					  'config' => 
					  array (
						'title' => $fieldname,
						'formElement' => 'container',
						'additionalClasses' => 'admin__field-small',
						'componentType' => 'container',
						'disabled' => false,
						//'component' => 'Magento_Ui/js/form/components/button',
						'component' => 'condor872_SearchableAttributes/js/form/product/linkbutton',
						'template' => 'ui/form/components/button/container',
						'additionalForGroup' => true,
						'provider' => false,
						//'url' => $url,
						//'onclick' => 'javascript:alert("wewe"); return false;',
						//'source' => 'product_details',
						'displayArea' => 'insideGroup',
						'required' => false,
						'sortOrder' => 20,
						'dataScope' => $scope,
						'actions' => $url,
						/*'after_element_html'=>'<script>jQuery(".action-basic[data-index=\''.$scope.'\']").on("click", function(){
							alert("cliccao su '.$scope.'");
						});</script>'*/
					  ),
					),
				  ),
			);
			return $array;
		}
		public function createModalButton($attribute_id,$modaltitle){
			$array=array (
				  'arguments' => 
				  array (
					'data' => 
					array (
					  'config' => 
					  array (
						'title' => "Aggiungi Opzioni",
						'formElement' => 'container',
						'additionalClasses' => 'admin__field-small',
						'componentType' => 'container',
						'disabled' => false,
						'component' => 'Magento_Ui/js/form/components/button',
						'template' => 'ui/form/components/button/container',
						'additionalForGroup' => true,
						'provider' => false,
						'displayArea' => 'insideGroup',
						'required' => false,
						'sortOrder' => 20,
						'actions' => [
							[
								"targetName" => "product_form.product_form.add_options_modal_fieldset.add_options_container.attribute_id_option_request",
								"actionName" => "value",
								"params" => [$attribute_id],
							],
							[
								"targetName" => "product_form.product_form.add_options_modal_fieldset.add_options_container.add_options_modal",
								"actionName" => "openModal"
							],
							[
								"targetName" => "product_form.product_form.add_options_modal_fieldset.add_options_container.add_options_modal.add_options_modal_loader",
								"actionName" => "render"
							],
						]
					  ),
					),
				  ),
			);
			return $array;
		}
}
?>