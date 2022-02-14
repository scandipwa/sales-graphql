<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 */
namespace ScandiPWA\SalesGraphQl\Model;

use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use ScandiPWA\SalesGraphQl\Api\OrderViewAuthorizationInterface;

class OrderViewAuthorization implements OrderViewAuthorizationInterface
{
    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @param Config $orderConfig
     */
    public function __construct(
        Config $orderConfig
    ) {
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(Order $order, $customerId): bool
    {
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();

        if ($order->getId()
            && $order->getCustomerId()
            && $order->getCustomerId() == $customerId
            && in_array($order->getStatus(), $availableStatuses, true)
        ) {
            return true;
        }

        return false;
    }
}
