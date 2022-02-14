<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 */
namespace ScandiPWA\SalesGraphQl\Api;

use Magento\Sales\Model\Order;

/**
 * Interface ScandiPWA\SalesGraphQl\Api\OrderViewAuthorizationInterface
 *
 */
interface OrderViewAuthorizationInterface
{
    /**
     * Check if order can be viewed by user
     *
     * @param Order $order
     * @param int
     * @return bool
     */
    public function canView(Order $order, $customerId);
}
