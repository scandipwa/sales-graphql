<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * @package scandipwa/module-sales-graph-ql
 * @link https://github.com/scandipwa/sales-graphql
 */
declare(strict_types=1);

namespace ScandiPWA\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQl\Model\Resolver\OrderTotal as SourceOrderTotal;

/**
 * Resolve order totals taxes and discounts for order
 */
class OrderTotal extends SourceOrderTotal
{
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
        if (!(($value['model'] ?? null) instanceof OrderInterface)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var OrderInterface $order */
        $order = $value['model'];
        $currency = $order->getOrderCurrencyCode();
        $baseCurrency = $order->getBaseCurrencyCode();

        return [
            'base_grand_total' => ['value' => $order->getBaseGrandTotal(), 'currency' => $baseCurrency],
            'grand_total' => ['value' => $order->getGrandTotal(), 'currency' => $currency],
            'subtotal' => ['value' => $order->getSubtotal(), 'currency' => $currency],
            'total_tax' => ['value' => $order->getTaxAmount(), 'currency' => $currency],
            'taxes' => $this->getAppliedTaxesDetails($order),
            'discounts' => $this->getDiscountDetails($order),
            'total_shipping' => ['value' => $order->getShippingAmount(), 'currency' => $currency],
            'shipping_handling' => [
                'amount_excluding_tax' => [
                    'value' => $order->getShippingAmount(),
                    'currency' => $order->getOrderCurrencyCode()
                ],
                'amount_including_tax' => [
                    'value' => $order->getShippingInclTax(),
                    'currency' => $currency
                ],
                'total_amount' => [
                    'value' => $order->getShippingAmount(),
                    'currency' => $currency
                ],
                'taxes' => $this->getAppliedShippingTaxesDetails($order),
                'discounts' => $this->getShippingDiscountDetails($order),
            ],
            'model' => $order
        ];
    }

    /**
     * Retrieve applied taxes that apply to the order
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getAllAppliedTaxesOnOrders(OrderInterface $order): array
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $appliedTaxes = $extensionAttributes->getAppliedTaxes() ?? [];
        $allAppliedTaxOnOrders = [];

        foreach ($appliedTaxes as $taxIndex => $appliedTaxesData) {
            $allAppliedTaxOnOrders[$taxIndex] = [
                'title' => $appliedTaxesData->getDataByKey('title'),
                'percent' => $appliedTaxesData->getDataByKey('percent'),
                'amount' => $appliedTaxesData->getDataByKey('amount'),
            ];
        }

        return $allAppliedTaxOnOrders;
    }

    /**
     * Return taxes applied to the current order
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getAppliedTaxesDetails(OrderInterface $order): array
    {
        $allAppliedTaxOnOrders = $this->getAllAppliedTaxesOnOrders($order);
        $taxes = [];

        foreach ($allAppliedTaxOnOrders as $appliedTaxes) {
            $appliedTaxesArray = [
                'rate' => $appliedTaxes['percent'] ?? 0,
                'title' => $appliedTaxes['title'] ?? null,
                'amount' => [
                    'value' => $appliedTaxes['amount'] ?? 0,
                    'currency' => $order->getOrderCurrencyCode()
                ]
            ];
            $taxes[] = $appliedTaxesArray;
        }

        return $taxes;
    }

    /**
     * Return information about an applied discount
     * Return null if no description available
     * @param OrderInterface $order
     * @return array
     */
    public function getDiscountDetails(OrderInterface $order): array
    {
        $orderDiscounts = [];
        if (!($order->getDiscountDescription() === null && $order->getDiscountAmount() == 0)) {
            $orderDiscounts[] = [
                'label' => $order->getDiscountDescription(),
                'amount' => [
                    'value' => abs((float)$order->getDiscountAmount()),
                    'currency' => $order->getOrderCurrencyCode()
                ]
            ];
        }

        return $orderDiscounts;
    }

    /**
     * Retrieve applied shipping taxes on items for the orders
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getAppliedShippingTaxesForItems(OrderInterface $order): array
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $itemAppliedTaxes = $extensionAttributes->getItemAppliedTaxes() ?? [];
        $appliedShippingTaxesForItems = [];

        foreach ($itemAppliedTaxes as $appliedTaxForItem) {
            if ($appliedTaxForItem->getType() === "shipping") {
                foreach ($appliedTaxForItem->getAppliedTaxes() ?? [] as $taxLineItem) {
                    $taxItemIndexTitle = $taxLineItem->getDataByKey('title');
                    $appliedShippingTaxesForItems[$taxItemIndexTitle] = [
                        'title' => $taxLineItem->getDataByKey('title'),
                        'percent' => $taxLineItem->getDataByKey('percent'),
                        'amount' => $taxLineItem->getDataByKey('amount')
                    ];
                }
            }
        }

        return $appliedShippingTaxesForItems;
    }

    /**
     * Return taxes applied to the current order
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getAppliedShippingTaxesDetails(
        OrderInterface $order
    ): array {
        $appliedShippingTaxesForItems = $this->getAppliedShippingTaxesForItems($order);
        $shippingTaxes = [];

        foreach ($appliedShippingTaxesForItems as $appliedShippingTaxes) {
            $appliedShippingTaxesArray = [
                'rate' => $appliedShippingTaxes['percent'] ?? 0,
                'title' => $appliedShippingTaxes['title'] ?? null,
                'amount' => [
                    'value' => $appliedShippingTaxes['amount'] ?? 0,
                    'currency' => $order->getOrderCurrencyCode()
                ]
            ];
            $shippingTaxes[] = $appliedShippingTaxesArray;
        }

        return $shippingTaxes;
    }

    /**
     * Return information about an applied discount
     * Return null if no description available
     * @param OrderInterface $order
     * @return array
     */
    public function getShippingDiscountDetails(OrderInterface $order): array
    {
        $shippingDiscounts = [];

        if (!($order->getDiscountDescription() === null && $order->getShippingDiscountAmount() == 0)) {
            $shippingDiscounts[] =
                [
                    'label' => $order->getDiscountDescription(),
                    'amount' => [
                        'value' => abs((float)$order->getShippingDiscountAmount()),
                        'currency' => $order->getOrderCurrencyCode()
                    ]
                ];
        }

        return $shippingDiscounts;
    }
}
