<?php

namespace condor872\Searchableattributes\Plugin\Catalog\Product;

use Magento\Framework\App\ObjectManager;
use Sureshop\Base\Helper\Product\PriceManagement;
use Magento\Catalog\Controller\Adminhtml\Product\Save as CatalogSave;
use Magento\Catalog\Model\ProductFactory;
use Sureshop\Base\Helper\Product\ProductWebsites;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute as EavEntityAttributeFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Sureshop\Base\Helper\Sunsky\Sunsky;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\Product\Media\Config;
use Sureshop\Base\Model\SunskyProductsFactory;
use Sureshop\Base\Model\Ean\EanFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Sureshop\Base\Helper\Warehouse\Stock;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Catalog\Model\Product\Gallery\Processor as ImageProcessor;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Registry;
use condor872\AsyncFunctions\Helper\FunctionDizionary;
use condor872\FixGetUsedProducts\Helper\GetAllChildrens;


class Save
{
	
	public function __construct(
    ) {
        $this->objectManager = ObjectManager::getInstance();
        $this->productWebsites = $this->objectManager->create(ProductWebsites::class);
		$this->GetAllChildrens = $this->objectManager->create(GetAllChildrens::class);
        $this->storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $this->storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);
        $this->action = $this->objectManager->create(Action::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->readHandler = $this->objectManager->create(ReadHandler::class);
        $this->productGallery = $this->objectManager->create(Gallery::class);
        $this->connection = $this->objectManager->get(ResourceConnection::class)->getConnection();
        $this->entityManager = $this->objectManager->get(Attribute::class);
        $this->productFactory = $this->objectManager->create(ProductFactory::class);
        $this->eavEntityAttributeFactory = $this->objectManager->create(EavEntityAttributeFactory::class);
        $this->categoryLinkRepository = $this->objectManager->create(CategoryLinkManagementInterface::class);
        $this->sunskyHelper = $this->objectManager->create(Sunsky::class);
        $this->sunskyProductsFactory = $this->objectManager->create(SunskyProductsFactory::class);
        $this->mediaGalleryEntryConverterPool = $this->objectManager->create(EntryConverterPool::class);
        $this->messageManager = $this->objectManager->get(MessageManagerInterface::class);
        $this->fileSystem = $this->objectManager->create(Filesystem::class);
        $this->productMediaConfig = $this->objectManager->get(Config::class);
        $this->eanFactory = $this->objectManager->create(EanFactory::class);
        $this->stockHelper = $this->objectManager->create(Stock::class);
        $this->storeAdmin = $this->storeRepository->get('admin');
        $this->configurable = $this->objectManager->create(Configurable::class);
		$this->AsyncHelper = $this->objectManager->create(FunctionDizionary::class);
        $this->curl = $this->objectManager->create(Curl::class);
        $this->sourceItemInterfaceFactory = $this->objectManager->get(SourceItemInterfaceFactory::class);
        $this->imageProcessor = $this->objectManager->get(ImageProcessor::class);
    }
	
	public function afterExecute(CatalogSave $instance, $result) {
        $this->instance = $instance;
        $data = $instance->getRequest()->getPostValue();
        $storeId = $instance->getRequest()->getParam('store', 0);
        $configurableMatrix = json_decode($data['configurable-matrix-serialized'], true);
        if ($configurableMatrix && count($configurableMatrix) > 0) {
            $instance->getRequest()->setParam('type', 'configurable');
        }
        \Magento\Framework\Profiler::start('sureshop_save');
        try {
            $product = $this->productRepository->get($data['product']['sku']);
        } catch (\Exception $e) {
            $product = null;
        }
        if ($product) {
			$this->allsaveasync($product->getId(), $data, $storeId);
			/*
			
			$this->fixSunskyCodes($product);
			$this->stockHelper->SyncSunskyPriceInSource($product);
            $this->setMarketplaceSetAttributes($product, $data, $storeId);				
            $this->updateVariationNamesInStores($product->getId(), $data, $storeId);
            $this->triggerSourceItemsSave($product->getId());
            $this->updatePriceVariables($product, $storeId);
            $this->copyToChildren($product->getId(), $storeId);
            $this->triggerChildrenPrices($product->getId(), $storeId);
            if ($storeId == $this->storeAdmin->getId()) {
                $this->updateSunskyProduct($product);
                $this->copyChildrenToWebsites($product, $data);
                $this->copyChildrenToStores($product, $data);
                $this->copyImagesToChildren($product->getId(), $data);
                //$this->copyBackordersToChildren($product, $data, $storeId);
                $this->setEan($product, $storeId);
                $this->setChildrenEan($product, $storeId);
                $this->copyImages($product, $data);
                $this->setStockQuantities($product, $data);
                /*if (isset($data['product']['item_no'])) {
                    $this->deleteTmpFolder($data['product']['item_no']);
                }*/
				/*
                $this->linkUpSellProducts($product->getData('sunsky_item_no'));
            }
            $toOrderQnt = null;
            if (isset($data['sources'])) {
                foreach ($data['sources']['assigned_sources'] as $assignedSource) {
                    if ($assignedSource['source_code'] == 'to_order') {
                        $toOrderQnt = $assignedSource['quantity'];
                    }
                }    
            }
            $this->processSalableChanges($product, $toOrderQnt);
			*/
        }
        return $result;
		
    }

}