<?php

namespace condor872\SearchableAttributes\Ui\Component\Listing\Column;

class CustomColumnCreator
{
    /**
     * @var \Magento\Framework\View\Element\UiComponentFactory
     */
    private $componentFactory;

    public function __construct(
        \Magento\Framework\View\Element\UiComponentFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
    }

    public function addColumnFromExistingColumn(
        \Magento\Framework\View\Element\UiComponentInterface $existingColumn,
        $columnName,
        $label,
        $sortOrder
    ) {
        $config = $existingColumn->getConfiguration();
        $config['label'] = $label;

        $arguments = [
            'data' => [
                'config' => $config,
                'sortOrder' => $sortOrder
            ],
            'context' => $existingColumn->getContext(),
        ];

        return $this->componentFactory->create($columnName, 'column', $arguments);
    }
}
