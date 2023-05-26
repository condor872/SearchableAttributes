<?php
namespace condor872\SearchableAttributes\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Catalog\Model\Entity\Attribute;
use Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Front extends Generic
{
        /**
     * @var Yesno
     */
    protected $_yesNo;

    /**
     * @param Magento\Config\Model\Config\Source\Yesno $yesNo
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Yesno $yesNo,
        PropertyLocker $propertyLocker,
        array $data = []
    ) {
        $this->_yesNo = $yesNo;
        $this->propertyLocker = $propertyLocker;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    public function aroundGetFormHtml(
        \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front $subject,
        \Closure $proceed
    ) {
        $attributeObject = $this->_coreRegistry->registry('entity_attribute');
        //Your plugin code
        $yesnoSource = $this->_yesNo->toOptionArray();
        $form = $subject->getForm();
        $fieldset = $form->getElement('front_fieldset');


        $fieldset->addField(
            'make_searchable',
            'select',
            [
                'name' => 'make_searchable',
                'label' => __('Vuoi rendere questo attributo ricercabile?'),
                'title' => __('Vuoi rendere questo attributo ricercabile?'),
                'note' => __('Selezionando Sì, potrai cercare i valori.'),
                'values' => $yesnoSource,
            ]
        );
        $fieldset->addField(
            'is_mappable',
            'select',
            [
                'name' => 'is_mappable',
                'label' => __('Disponibile per il mapping?'),
                'title' => __('Vuoi rendere questo attributo disponibile?'),
                'note' => __('Vuoi rendere questo attributo disponibile per il mapping durante la creazione dei set di attributi?'),
                'values' => $yesnoSource,
            ]
        );
        $fieldset->addField(
            'has_global_label',
            'select',
            [
                'name' => 'has_global_label',
                'label' => __('Le Opzioni Sono uguali per tutte le lingue?'),
                'title' => __('Vuoi rendere questo attributo disponibile?'),
                'note' => __('Le opzioni di questo attributo sono uguali per tutte le lingua e non vanno tradotte? Per esempio: Attributo marca ha dentro tutti i nomi delle marche che non vanno tradotti in quanto uguali su tutte le lingue'),
                'values' => $yesnoSource,
            ]
        );
        $fieldset->addField(
            'available_for_templates',
            'select',
            [
                'name' => 'available_for_templates',
                'label' => __('Disponibile nei template?'),
                'title' => __('Vuoi rendere questo attributo disponibile?'),
                'default' => 1,
                'values' => $yesnoSource,
            ]
        );
        
        $this->setForm($form);
        $form->setValues($attributeObject->getData());
        $this->propertyLocker->lock($form);

        return $proceed();
    }
}
