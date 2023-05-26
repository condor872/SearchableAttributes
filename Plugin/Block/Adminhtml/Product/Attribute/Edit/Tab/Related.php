<?php

namespace condor872\SearchableAttributes\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab;
use Magento\Store\Model\ScopeInterface;

/**

* Block for edit page

*/

class Related extends \Magento\Backend\Block\Template
{

    protected $_template = 'related.phtml';
	protected $request;

	
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
		\Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\View\LayoutInterface $layout,
        \condor872\Core\Helper\Core $giuseppecore,
        \condor872\EBayConnector\Helper\Client $ebayclient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
		array $data = []
    ) {
       $this->getLayout = $layout;
       parent::__construct($context, $data);
	   $this->request = $request;
       $this->giuseppecore = $giuseppecore;
       $this->ebayclient = $ebayclient;
	   $this->_resourceConnection = $resourceConnection;
       $this->scopeConfig = $scopeConfig;
       $this->_backendUrl = $backendUrl;
       $this->_wysiwygConfig = $wysiwygConfig;
    }

    public function test(){
        return "ciao";
    }

    public function getlink(){
        $attribute_url=$this->_backendUrl->getUrl("searchoptions/attributesgrid/index");
        return $attribute_url;
    }

 
    

}