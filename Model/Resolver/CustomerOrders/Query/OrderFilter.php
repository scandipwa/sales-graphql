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

namespace ScandiPWA\SalesGraphQl\Model\Resolver\CustomerOrders\Query;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Sales\Model\Order\Config;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderFilter as CoreOrderFilter;

/**
 * Order filter allows to filter collection using 'increment_id' as order number, from the search criteria.
 */
class OrderFilter extends CoreOrderFilter
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Translator field from graphql to collection field
     *
     * @var string[]
     */
    protected $fieldTranslatorArray = [
        'number' => 'increment_id',
    ];

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param string[] $fieldTranslatorArray
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        Config $orderConfig,
        array $fieldTranslatorArray = []
    ) {
        parent::__construct(
            $scopeConfig,
            $filterBuilder,
            $filterGroupBuilder,
            $fieldTranslatorArray
        );

        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->orderConfig = $orderConfig;
        $this->fieldTranslatorArray = array_replace($this->fieldTranslatorArray, $fieldTranslatorArray);
    }

    /**
     * Create filter for filtering the requested categories id's based on url_key, ids, name in the result.
     *
     * @param array $args
     * @param int $userId
     * @param int $storeId
     * @return FilterGroup[]
     */
    public function createFilterGroups(
        array $args,
        int $userId,
        int $storeId
    ): array {
        $filterGroups = [];

        $this->filterGroupBuilder->setFilters(
            [$this->filterBuilder->setField('customer_id')->setValue($userId)->setConditionType('eq')->create()]
        );
        $filterGroups[] = $this->filterGroupBuilder->create();

        // Next lines are added to filter order status by visible on front statuses
        $this->filterGroupBuilder->setFilters(
            [$this->filterBuilder->setField('status')->setValue($this->orderConfig->getVisibleOnFrontStatuses())->setConditionType('in')->create()]
        );
        $filterGroups[] = $this->filterGroupBuilder->create();

        if (isset($args['filter'])) {
            $filters = [];

            foreach ($args['filter'] as $field => $cond) {
                if (isset($this->fieldTranslatorArray[$field])) {
                    $field = $this->fieldTranslatorArray[$field];
                }

                foreach ($cond as $condType => $value) {
                    if ($condType === 'match') {
                        if (is_array($value)) {
                            throw new InputException(__('Invalid match filter'));
                        }

                        $searchValue = str_replace('%', '', $value);
                        $filters[] = $this->filterBuilder->setField($field)
                            ->setValue("%{$searchValue}%")
                            ->setConditionType('like')
                            ->create();
                    } else {
                        $filters[] = $this->filterBuilder->setField($field)
                            ->setValue($value)
                            ->setConditionType($condType)
                            ->create();
                    }
                }
            }

            $this->filterGroupBuilder->setFilters($filters);
            $filterGroups[] = $this->filterGroupBuilder->create();
        }

        return $filterGroups;
    }
}
