<?php

namespace ScandiPWA\SalesGraphQl\Model\Resolver\CreditMemo;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use ScandiPWA\SalesGraphQl\Api\OrderViewAuthorizationInterface;

/**
 * Resolver for Invoice Items
 */
class PrintCreditMemo implements ResolverInterface
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var OrderFormatter
     */
    protected $orderFormatter;

    /**
     * @var OrderViewAuthorizationInterface
     */
    protected $orderViewAuthorizationInterface;

    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderFormatter $orderFormatter,
        OrderViewAuthorizationInterface $orderViewAuthorizationInterface
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
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
        $refund = $this->creditmemoRepository->get($args['refundId']);
        $order = $refund->getOrder();

        if (!$this->orderViewAuthorizationInterface->canView($order, $customerId)) {
            throw new GraphQlInputException(__('Current user is not allowed to print this order'));
        }

        return $this->orderFormatter->format($order);
    }
}
