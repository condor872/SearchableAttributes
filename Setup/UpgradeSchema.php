<?php

namespace condor872\SearchableAttributes\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
  
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        if (version_compare($context->getVersion(), '1.0.1') < 0) 
		{
            $setup->startSetup();
            $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
            'make_searchable',
            ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            'length' => '1',
            'nullable' => false,
            'unsigned' => true,
            'default' => '0',
            'comment' => 'This is a custom field']);
            $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
                'is_mappable',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'length' => '1',
                'nullable' => false,
                'unsigned' => true,
                'default' => '1',
                'comment' => 'This is a custom field']);
            $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
                'has_global_label',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                 'length' => '1',
                 'nullable' => false,
                 'unsigned' => true,
                 'default' => '0',
                 'comment' => 'This is a custom field']);
            $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
                'available_for_templates',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                 'length' => '1',
                 'nullable' => false,
                 'unsigned' => true,
                 'default' => '1',
                 'comment' => 'This is a custom field']);
            $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
                'related_attribute_id',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                 'nullable' => true,
                 'unsigned' => true,
                 'default' => null,
                 'comment' => 'Related Attribute ID']);
            $setup->getConnection()->addColumn(
            $setup->getTable('eav_attribute_option'),
                'related_option_id',
                ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                 'nullable' => true,
                 'unsigned' => true,
                 'default' => 0,
                 'comment' => 'related_option_id']);
            $setup->endSetup();
        } 
    } 
}