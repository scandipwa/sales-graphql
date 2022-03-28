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

namespace Scandipwa\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Model\Reorder\Data\Error;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\SalesGraphQl\Model\Resolver\Reorder as CoreReorder;
use Magento\Sales\Model\Reorder\Reorder as ResolveReorder;

/**
 * ReOrder customer order
 */
class Reorder extends CoreReorder
{
    /**
     * Order number
     */
    public const ARGUMENT_ORDER_NUMBER = 'orderNumber';

    /**
     * @var OrderFactory
     */
    public OrderFactory $orderFactory;

    /**
     * @var ResolveReorder
     */
    public ResolveReorder $reorder;

    /**
     * @param ResolveReorder $reorder
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        ResolveReorder $reorder,
        OrderFactory $orderFactory
    ) {
        parent::__construct(
            $reorder,
            $orderFactory
        );

        $this->orderFactory = $orderFactory;
        $this->reorder = $reorder;
    }

    /**
     * Removed store filtering for reorder functionality
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $currentUserId = $context->getUserId();
        $orderNumber = $args['orderNumber'] ?? '';

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderStoreId = (string)$order->getStore()->getId();

        if ((int)$order->getCustomerId() !== $currentUserId) {
            throw new GraphQlInputException(
                __('Order number "%1" doesn\'t belong to the current customer', $orderNumber)
            );
        }

        $reorderOutput = $this->reorder->execute($orderNumber, $orderStoreId);

        return [
            'cart' => [
                'model' => $reorderOutput->getCart(),
            ],
            'userInputErrors' => \array_map(
                function (Error $error) {
                    return [
                        'path' => [self::ARGUMENT_ORDER_NUMBER],
                        'code' => $error->getCode(),
                        'message' => $error->getMessage(),
                    ];
                },
                $reorderOutput->getErrors()
            )
        ];
    }
}
