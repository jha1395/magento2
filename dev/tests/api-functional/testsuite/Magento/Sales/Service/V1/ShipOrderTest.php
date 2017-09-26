<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Service\V1;

/**
 * API test for creation of Shipment for certain Order.
 */
class ShipOrderTest extends \Magento\TestFramework\TestCase\WebapiAbstract
{
    const SERVICE_READ_NAME = 'salesShipOrderV1';
    const SERVICE_VERSION = 'V1';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->shipmentRepository = $this->objectManager->get(
            \Magento\Sales\Api\ShipmentRepositoryInterface::class
        );
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/order_configurable_product.php
     */
    public function testConfigurableShipOrder()
    {
        /** @var \Magento\Sales\Model\Order $existingOrder */
        $existingOrder = $this->objectManager->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        $shipmentId = $this->sendOrderShipRequest($existingOrder);

        try {
            $shipment = $this->shipmentRepository->get($shipmentId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->fail('Failed asserting that Shipment was created');
        }

        $orderedQty = 0;
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($existingOrder->getItems() as $item) {
            if ($item->isDummy()) {
                continue;
            }
            $orderedQty += $item->getQtyOrdered();
        }

        $this->assertEquals(
            (int)$shipment->getTotalQty(),
            (int)$orderedQty,
            'Failed asserting that quantity of ordered and shipped items is equal'
        );
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/order_new.php
     */
    public function testShipOrder()
    {
        /** @var \Magento\Sales\Model\Order $existingOrder */
        $existingOrder = $this->objectManager->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        $shipmentId = $this->sendOrderShipRequest($existingOrder);

        try {
            $this->shipmentRepository->get($shipmentId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->fail('Failed asserting that Shipment was created');
        }

        /** @var \Magento\Sales\Model\Order $updatedOrder */
        $updatedOrder = $this->objectManager->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        $this->assertNotEquals(
            $existingOrder->getStatus(),
            $updatedOrder->getStatus(),
            'Failed asserting that Order status was changed'
        );
    }

    /**
     * Sends API request for creation shipment by order.
     *
     * @param \Magento\Sales\Model\Order $existingOrder
     * @return int
     */
    private function sendOrderShipRequest(\Magento\Sales\Model\Order $existingOrder)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/order/' . $existingOrder->getId() . '/ship',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'execute',
            ],
        ];

        $requestData = [
            'orderId' => $existingOrder->getId(),
        ];

        $shipmentId = (int)$this->_webApiCall($serviceInfo, $requestData);
        $this->assertNotEmpty($shipmentId);

        return $shipmentId;
    }
}
