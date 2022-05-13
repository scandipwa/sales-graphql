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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\SalesGraphQl\Model\Resolver\Invoices as SourceInvoices;

/**
 * Resolver for Invoice
 */
class Invoices extends SourceInvoices
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

        /** @var OrderInterface $orderModel */
        $orderModel = $value['model'];
        $invoices = [];

        /** @var InvoiceInterface $invoice */
        foreach ($orderModel->getInvoiceCollection() as $invoice) {
            $invoices[] = [
                'id' => base64_encode((string) $invoice->getEntityId()),
                'number' => $invoice['increment_id'],
                'comments' => $this->getInvoiceComments($invoice),
                'model' => $invoice,
                'order' => $orderModel
            ];
        }

        return $invoices;
    }

    /**
     * Get comments invoice in proper format
     *
     * @param InvoiceInterface $invoice
     * @return array
     */
    public function getInvoiceComments(InvoiceInterface $invoice): array
    {
        $comments = [];

        foreach ($invoice->getComments() as $comment) {
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
