<?php

namespace ScandiPWA\SalesGraphQl\Model\Resolver\Shipment;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use ScandiPWA\SalesGraphQl\Api\OrderViewAuthorizationInterface;

/**
 * Resolver for Invoice Items
 */
class PrintShipment implements ResolverInterface
{
    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var OrderFormatter
     */
    protected OrderFormatter $orderFormatter;

    /**
     * @var OrderViewAuthorizationInterface
     */
    protected OrderViewAuthorizationInterface $orderViewAuthorizationInterface;

    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderFormatter $orderFormatter
     * @param OrderViewAuthorizationInterface $orderViewAuthorizationInterface
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        OrderFormatter $orderFormatter,
        OrderViewAuthorizationInterface $orderViewAuthorizationInterface
    ) {
        $this->shipmentRepository = $shipmentRepository;
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
            $shipment = $this->shipmentRepository->get($args['shipmentId']);
            $order = $shipment->getOrder();
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        if (!$this->orderViewAuthorizationInterface->canView($order, $customerId)) {
            throw new GraphQlInputException(__('Current user is not allowed to print this order'));
        }

        return $this->orderFormatter->format($order);
    }
}
