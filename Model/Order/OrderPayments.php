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

namespace ScandiPWA\SalesGraphQl\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQl\Model\Order\OrderPayments as CoreOrderPayments;

/**
 * Class to get the order payment details
 */
class OrderPayments extends CoreOrderPayments
{
    /**
     * Get the order payment method
     *
     * @param OrderInterface $orderModel
     * @return array
     */
    public function getOrderPaymentMethod(OrderInterface $orderModel): array
    {
        $orderPayment = $orderModel->getPayment();

        return [
            [
                'name' => $orderPayment->getAdditionalInformation()['method_title'] ?? '',
                'type' => $orderPayment->getMethod(),
                'additional_data' => [],
                'purchase_number' => $orderPayment->getPoNumber()
            ]
        ];
    }
}
