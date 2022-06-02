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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\OrderItem\DataProvider as SourceDataProvider;

/**
 * Data provider for order items
 */
class DataProvider extends SourceDataProvider
{
    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OptionsProcessor
     */
    protected $optionsProcessor;

    /**
     * @var int[]
     */
    protected $orderItemIds = [];

    /**
     * @var array
     */
    protected $orderItemList = [];

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OptionsProcessor $optionsProcessor
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OptionsProcessor $optionsProcessor
    ) {
        parent::__construct(
            $orderItemRepository,
            $productRepository,
            $orderRepository,
            $searchCriteriaBuilder,
            $optionsProcessor
        );

        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->optionsProcessor = $optionsProcessor;
    }

    /**
     * Add order item id to list for fetching
     *
     * @param int $orderItemId
     */
    public function addOrderItemId(int $orderItemId): void
    {
        if (!in_array($orderItemId, $this->orderItemIds)) {
            $this->orderItemList = [];
            $this->orderItemIds[] = $orderItemId;
        }
    }

    /**
     * Get order item by item id
     *
     * @param int $orderItemId
     * @return array
     */
    public function getOrderItemById(int $orderItemId): array
    {
        $orderItems = $this->fetch();

        if (!isset($orderItems[$orderItemId])) {
            return [];
        }

        return $orderItems[$orderItemId];
    }

    /**
     * Fetch order items and return in format for GraphQl
     *
     * @return array
     */
    protected function fetch()
    {
        if (empty($this->orderItemIds) || !empty($this->orderItemList)) {
            return $this->orderItemList;
        }

        $itemSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderItemInterface::ITEM_ID, $this->orderItemIds, 'in')
            ->create();

        $orderItems = $this->orderItemRepository->getList($itemSearchCriteria)->getItems();
        $productList = $this->fetchProducts($orderItems);
        $orderList = $this->fetchOrders($orderItems);

        foreach ($orderItems as $orderItem) {
            /** @var ProductInterface $associatedProduct */
            $associatedProduct = $productList[$orderItem->getProductId()] ?? null;
            /** @var OrderInterface $associatedOrder */
            $associatedOrder = $orderList[$orderItem->getOrderId()];
            $itemOptions = $this->optionsProcessor->getItemOptions($orderItem);
            $this->orderItemList[$orderItem->getItemId()] = [
                'id' => base64_encode((string)$orderItem->getItemId()),
                'associatedProduct' => $associatedProduct,
                'model' => $orderItem,
                'product_name' => $orderItem->getName(),
                'product_sku' => $orderItem->getSku(),
                'product_url_key' => $associatedProduct ? $associatedProduct->getUrlKey() : null,
                'product_type' => $orderItem->getProductType(),
                'status' => $orderItem->getStatus(),
                'discounts' => $this->getDiscountDetails($associatedOrder, $orderItem),
                'product_sale_price' => [
                    'value' => $orderItem->getPrice(),
                    'currency' => $associatedOrder->getOrderCurrencyCode()
                ],
                'selected_options' => $itemOptions['selected_options'],
                'entered_options' => $itemOptions['entered_options'],
                'quantity_ordered' => $orderItem->getQtyOrdered(),
                'quantity_shipped' => $orderItem->getQtyShipped(),
                'quantity_refunded' => $orderItem->getQtyRefunded(),
                'quantity_invoiced' => $orderItem->getQtyInvoiced(),
                'quantity_canceled' => $orderItem->getQtyCanceled(),
                'quantity_returned' => $orderItem->getQtyReturned(),
                'row_subtotal' => [
                    'value' => $orderItem->getRowTotal(),
                    'currency' => $associatedOrder->getOrderCurrencyCode()
                ]
            ];
        }

        return $this->orderItemList;
    }

    /**
     * Fetch associated products for order items
     *
     * @param array $orderItems
     * @return array
     */
    protected function fetchProducts(array $orderItems): array
    {
        $productIds = array_map(
            function ($orderItem) {
                return $orderItem->getProductId();
            },
            $orderItems
        );

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();
        $productList = [];

        foreach ($products as $product) {
            $productList[$product->getId()] = $product;
        }

        return $productList;
    }

    /**
     * Fetch associated order for order items
     *
     * @param array $orderItems
     * @return array
     */
    protected function fetchOrders(array $orderItems): array
    {
        $orderIds = array_map(
            function ($orderItem) {
                return $orderItem->getOrderId();
            },
            $orderItems
        );

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $orderIds, 'in')
            ->create();
        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        $orderList = [];

        foreach ($orders as $order) {
            $orderList[$order->getEntityId()] = $order;
        }

        return $orderList;
    }

    /**
     * Returns information about an applied discount
     *
     * @param OrderInterface $associatedOrder
     * @param OrderItemInterface $orderItem
     * @return array
     */
    protected function getDiscountDetails(OrderInterface $associatedOrder, OrderItemInterface $orderItem) : array
    {
        if (
            $associatedOrder->getDiscountDescription() === null
            && $orderItem->getDiscountAmount() == 0
            && $associatedOrder->getDiscountAmount() == 0
        ) {
            $discounts = [];
        } else {
            $discounts [] = [
                'label' => $associatedOrder->getDiscountDescription() ?? __('Discount'),
                'amount' => [
                    'value' => abs((float)$orderItem->getDiscountAmount()),
                    'currency' => $associatedOrder->getOrderCurrencyCode()
                ]
            ];
        }

        return $discounts;
    }
}
