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

namespace ScandiPWA\SalesGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders as CoreCustomerOrders;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderFilter;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;

/**
 * Orders data resolver
 */
class CustomerOrders extends CoreCustomerOrders
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderFilter
     */
    protected $orderFilter;

    /**
     * @var OrderFormatter
     */
    protected $orderFormatter;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * CustomerOrders constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderFilter $orderFilter
     * @param OrderFormatter $orderFormatter
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderFilter $orderFilter,
        OrderFormatter $orderFormatter,
        SortOrderBuilder $sortOrderBuilder
    ) {
        parent::__construct(
            $orderRepository,
            $searchCriteriaBuilder,
            $orderFilter,
            $orderFormatter
        );

        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderFilter = $orderFilter;
        $this->orderFormatter = $orderFormatter;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        $userId = $context->getUserId();
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        try {
            $searchResult = $this->getSearchResult($args, (int)$userId, (int)$store->getId());
            $maxPages = (int)ceil($searchResult->getTotalCount() / $searchResult->getPageSize());
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        $ordersArray = [];

        foreach ($searchResult->getItems() as $orderModel) {
            $ordersArray[] = $this->orderFormatter->format($orderModel);
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' =>  $ordersArray,
            'page_info' => [
                'page_size' => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurPage(),
                'total_pages' => $maxPages,
            ]
        ];
    }

    /**
     * Get search result from graphql query arguments
     *
     * @param array $args
     * @param int $userId
     * @param int $storeId
     * @return OrderSearchResultInterface
     * @throws InputException
     */
    private function getSearchResult(array $args, int $userId, int $storeId)
    {
        $filterGroups = $this->orderFilter->createFilterGroups($args, $userId, (int)$storeId);
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);

        $sortOrder = $this->sortOrderBuilder->setField('entity_id')->setDirection(SortOrder::SORT_DESC)->create();
        $this->searchCriteriaBuilder->setSortOrders([$sortOrder]);

        if (isset($args['currentPage'])) {
            $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        }

        if (isset($args['pageSize'])) {
            $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        }

        return $this->orderRepository->getList($this->searchCriteriaBuilder->create());
    }
}
