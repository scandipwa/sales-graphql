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

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQl\Model\Order\OrderAddress as CoreOrderAddress;

/**
 * Class to get the order address details
 */
class OrderAddress extends CoreOrderAddress
{
    /**
     * Get the order Shipping address
     *
     * @param OrderInterface $order
     * @return array|null
     */
    public function getOrderShippingAddress(
        OrderInterface $order
    ): ?array {
        $shippingAddress = null;

        if ($order->getShippingAddress()) {
            $shippingAddress = $this->formatAddressData($order->getShippingAddress());
        }

        return $shippingAddress;
    }

    /**
     * Get the order billing address
     *
     * @param OrderInterface $order
     * @return array|null
     */
    public function getOrderBillingAddress(
        OrderInterface $order
    ): ?array {
        $billingAddress = null;

        if ($order->getBillingAddress()) {
            $billingAddress = $this->formatAddressData($order->getBillingAddress());
        }

        return $billingAddress;
    }

    /**
     * Customer Order address data formatter
     *
     * @param OrderAddressInterface $orderAddress
     * @return array
     */
    public function formatAddressData(
        OrderAddressInterface $orderAddress
    ): array {
        // Changed country_code to country_id
        return
            [
                'firstname' => $orderAddress->getFirstname(),
                'lastname' => $orderAddress->getLastname(),
                'middlename' => $orderAddress->getMiddlename(),
                'postcode' => $orderAddress->getPostcode(),
                'prefix' => $orderAddress->getPrefix(),
                'suffix' => $orderAddress->getSuffix(),
                'street' => $orderAddress->getStreet(),
                'country_id' => $orderAddress->getCountryId(),
                'city' => $orderAddress->getCity(),
                'company' => $orderAddress->getCompany(),
                'fax' => $orderAddress->getFax(),
                'telephone' => $orderAddress->getTelephone(),
                'vat_id' => $orderAddress->getVatId(),
                'region_id' => $orderAddress->getRegionId(),
                'region' => $orderAddress->getRegion()
            ];
    }
}
