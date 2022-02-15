<?php

namespace ScandiPWA\SalesGraphQl\Model\Resolver\Invoice;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use ScandiPWA\SalesGraphQl\Api\OrderViewAuthorizationInterface;

/**
 * Resolver for Invoice Items
 */
class PrintInvoice implements ResolverInterface
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected InvoiceRepositoryInterface $invoiceRepository;

    /**
     * @var OrderFormatter
     */
    protected OrderFormatter $orderFormatter;

    /**
     * @var OrderViewAuthorizationInterface
     */
    protected OrderViewAuthorizationInterface $orderViewAuthorizationInterface;

    /**
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderFormatter $orderFormatter
     * @param OrderViewAuthorizationInterface $orderViewAuthorizationInterface
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        OrderFormatter $orderFormatter,
        OrderViewAuthorizationInterface $orderViewAuthorizationInterface
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderFormatter = $orderFormatter;
        $this->orderViewAuthorizationInterface = $orderViewAuthorizationInterface;
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
        $customerId = $context->getUserId();

        try {
            $invoice = $this->invoiceRepository->get($args['invoiceId']);
            $order = $invoice->getOrder();
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        if (!$this->orderViewAuthorizationInterface->canView($order, $customerId)) {
            throw new GraphQlInputException(__('Current user is not allowed to print this order'));
        }

        return $this->orderFormatter->format($order);
    }
}
