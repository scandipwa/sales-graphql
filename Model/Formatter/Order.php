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

namespace ScandiPWA\SalesGraphQl\Model\Formatter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Rss\UrlBuilderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Rss\Signature;
use Magento\SalesGraphQl\Model\Order\OrderAddress;
use Magento\SalesGraphQl\Model\Order\OrderPayments;
use Magento\SalesGraphQl\Model\Formatter\Order as SourceOrder;
use Magento\Store\Model\ScopeInterface;

/**
 * Format order model for graphql schema
 */
class Order extends SourceOrder
{
    /**
     * Xml pah to order rss enabled status
     */
    const XML_PATH_ORDER_RSS_ENABLED_STATUS = 'rss/order/status';

    /**
     * @var OrderAddress
     */
    protected $orderAddress;

    /**
     * @var OrderPayments
     */
    protected $orderPayments;

    /**
     * @var UrlBuilderInterface
     */
    protected $rssUrlBuilder;

    /**
     * @var Signature
     */
    protected $signature;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param OrderAddress $orderAddress
     * @param OrderPayments $orderPayments
     */
    public function __construct(
        OrderAddress $orderAddress,
        OrderPayments $orderPayments,
        UrlBuilderInterface $rssUrlBuilder,
        Signature $signature,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct(
          $orderAddress,
          $orderPayments
        );

        $this->orderAddress = $orderAddress;
        $this->orderPayments = $orderPayments;
        $this->rssUrlBuilder = $rssUrlBuilder;
        $this->signature = $signature;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Format order model for graphql schema
     *
     * @param OrderInterface $orderModel
     * @return array
     */
    public function format(OrderInterface $orderModel): array
    {
        return [
            'created_at' => $orderModel->getCreatedAt(),
            'grand_total' => $orderModel->getGrandTotal(),
            'id' => base64_encode($orderModel->getEntityId()),
            'increment_id' => $orderModel->getIncrementId(),
            'number' => $orderModel->getIncrementId(),
            'order_date' => $orderModel->getCreatedAt(),
            'order_number' => $orderModel->getIncrementId(),
            'status' => $orderModel->getStatusLabel(),
            'shipping_method' => $orderModel->getShippingDescription(),
            'shipping_address' => $this->orderAddress->getOrderShippingAddress($orderModel),
            'billing_address' => $this->orderAddress->getOrderBillingAddress($orderModel),
            'payment_methods' => $this->orderPayments->getOrderPaymentMethod($orderModel),
            'rss_link' => $this->getRssLink($orderModel),
            'can_reorder' => $orderModel->canReorder(),
            'model' => $orderModel,
            'comments' => $this->getOrderComments($orderModel)
        ];
    }

    /**
     * @param $order
     * @return mixed|null
     */
    public function getRssLink($order) {
        if (!$this->isRssAllowed()) {
            return null;
        }

        return $this->rssUrlBuilder->getUrl($this->getLinkParams($order));
    }

    /**
     * Retrieve order status url key
     *
     * @param OrderInterface $order
     * @return string
     */
    protected function getUrlKey($order)
    {
        $data = [
            'order_id' => $order->getId(),
            'increment_id' => $order->getIncrementId(),
            'customer_id' => $order->getCustomerId(),
        ];

        return base64_encode(json_encode($data));
    }

    /**
     * Get type, secure and query params for link.
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getLinkParams($order)
    {
        $data = $this->getUrlKey($order);

        return [
            'type' => 'order_status',
            '_secure' => true,
            '_query' => ['data' => $data, 'signature' => $this->signature->signData($data)],
        ];
    }

    /**
     * Check whether status notification is allowed
     *
     * @return bool
     */
    public function isRssAllowed()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ORDER_RSS_ENABLED_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get order comments in proper format
     *
     * @param OrderInterface $orderModel
     * @return array
     */
    public function getOrderComments(OrderInterface $orderModel): array
    {
        $comments = [];

        foreach ($orderModel->getStatusHistoryCollection() as $comment) {
            if ($comment->getIsVisibleOnFront()) {
                $comments[] = [
                    'timestamp' => $comment->getCreatedAt(),
                    'message' => $comment->getComment()
                ];
            }
        }

        return $comments;
    }
}
