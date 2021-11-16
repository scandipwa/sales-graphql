<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 */
declare(strict_types=1);

namespace ScandiPWA\SalesGraphQl\Model\OrderItem;

use Magento\SalesGraphQl\Model\OrderItem\OptionsProcessor as SourceOptionsProcessor;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Downloadable\Api\LinkRepositoryInterface;

/**
 * Process order item options to format for GraphQl output
 */
class OptionsProcessor extends SourceOptionsProcessor
{
    /**
     * Defines which selected option types are allowed
     */
    protected $selectedOptionAllowedTypes = ['field', 'area', 'file', 'date', 'date_time', 'time'];

    /**
     * Defines which entered option types are allowed
     */
    protected $enteredOptionAllowedTypes = ['drop_down', 'radio', 'checkbox', 'multiple'];

    /**
     * Orders item selected options array
     */
    protected $selectedOptions = [];

    /**
     * Orders item entered options array
     */
    protected $enteredOptions = [];

    /**
     * @var LinkRepositoryInterface
     */
    protected $linkRepository;

    public function __construct(
        LinkRepositoryInterface $linkRepository
    ) {
        $this->linkRepository = $linkRepository;
    }

    /**
     * Get Order item options.
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    public function getItemOptions(OrderItemInterface $orderItem): array
    {
        $this->selectedOptions = [];
        $this->enteredOptions = [];
        $options = $orderItem->getProductOptions();

        if ($options) {
            if (isset($options['options'])) {
                $this->processOptions($options['options']);
            }

            if (isset($options['attributes_info'])) {
               $this->processAttributesInfo($options['attributes_info']);
            }

            if (isset($options['bundle_options'])) {
                $this->processBundleOptions($options['bundle_options']);
            }

            if (isset($options['links'])) {
                $this->processDownloadableLinksOptions($orderItem, $options['links']);
            }
        }

        return ['selected_options' => $this->selectedOptions, 'entered_options' => $this->enteredOptions];
    }

    /**
     * Process options data
     *
     * @param array $options
     */
    protected function processOptions(array $options)
    {
        foreach ($options ?? [] as $option) {
            if (isset($option['option_type'])) {
                if (in_array($option['option_type'], $this->selectedOptionAllowedTypes)) {
                    if ($option['option_type'] === 'file') {
                        $value = $option['value'];
                    } else {
                        $value = $option['print_value'];
                    }

                    $this->selectedOptions[] = [
                        'label' => $option['label'],
                        'value' => $value,
                        'type' => $options['option_type'] ?? null
                    ];
                } elseif (in_array($option['option_type'], $this->enteredOptionAllowedTypes)) {
                    $this->enteredOptions[] = [
                        'label' => $option['label'],
                        'value' => $option['print_value'] ?? $option['value'],
                        'type' => $option['option_type'] ?? null
                    ];
                }
            }
        }
    }

    /**
     * Process attributes info data
     *
     * @param array $attributesInfo
     */
    protected function processAttributesInfo(array $attributesInfo)
    {
        foreach ($attributesInfo ?? [] as $option) {
            $this->selectedOptions[] = [
                'label' => $option['label'],
                'value' => $option['print_value'] ?? $option['value'],
            ];
        }
    }

    /**
     * Process bundle product options
     *
     * @param array $bundleOptions
     */
    protected function processBundleOptions(array $bundleOptions)
    {
        foreach ($bundleOptions ?? [] as $option) {
            $this->enteredOptions[] = [
                'label' => $option['label'],
                'items' => $option['value']
            ];
        }
    }

    /**
     * Process downloadable product links
     *
     * @param $product
     * @param array $links
     */
    protected function processDownloadableLinksOptions($product, array $links) {
        // Get the product downloadable links
        $productLinks = $this->linkRepository->getList($product->getSku());
        $linksOutput = [ 'label' => 'Links', 'linkItems' => []];

        foreach($productLinks as $link) {
            if (in_array($link->getId(), $links)) {
                $linksOutput['linkItems'][] = $link->getTitle();
            }
        }

        if (count($linksOutput['linkItems'])) {
            $this->selectedOptions[] = $linksOutput;
        }
    }
}
