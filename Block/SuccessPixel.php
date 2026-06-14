<?php

declare(strict_types=1);

namespace BerryPath\Flow\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\ScopeInterface;

class SuccessPixel extends Template
{
    private const XML_PATH_GENERAL_ENABLED = 'berrypath_flow/general/enabled';
    private const XML_PATH_SUCCESS_PIXEL_ENABLED = 'berrypath_flow/success_pixel/enabled';
    private const XML_PATH_PRODUCT_IDENTIFIER = 'berrypath_flow/product/product_identifier';

    protected $_template = 'BerryPath_Flow::success_pixel.phtml';

    private ?OrderInterface $order = null;

    public function __construct(
        Template\Context $context,
        private readonly CheckoutSession $checkoutSession,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function canRender(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_ENABLED, ScopeInterface::SCOPE_STORE)
            && $this->scopeConfig->isSetFlag(self::XML_PATH_SUCCESS_PIXEL_ENABLED, ScopeInterface::SCOPE_STORE)
            && $this->getOrder() !== null
            && $this->getOrderItems() !== [];
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->canRender()) {
            $this->pageConfig->addRemotePageAsset(
                Embed::LOADER_SCRIPT_URL,
                'js',
                [],
                'berrypath-flow-loader'
            );
        }

        return $this;
    }

    public function getOrderPayloadJson(): string
    {
        $payload = $this->getOrderPayload();
        $json = json_encode(
            $payload,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );

        return $json !== false ? $json : '{}';
    }

    /**
     * @return array{order_total: string, items: array<int, array<string, string>>}
     */
    private function getOrderPayload(): array
    {
        $order = $this->getOrder();
        if (!$order instanceof OrderInterface) {
            return ['order_total' => '', 'items' => []];
        }

        return [
            'order_total' => $this->formatAmount((float)$order->getGrandTotal()),
            'items' => $this->getOrderItems(),
        ];
    }

    private function getOrder(): ?OrderInterface
    {
        if ($this->order !== null) {
            return $this->order;
        }

        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order instanceof OrderInterface || !$order->getEntityId()) {
            return null;
        }

        $this->order = $order;

        return $this->order;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getOrderItems(): array
    {
        $order = $this->getOrder();
        if (!$order instanceof OrderInterface || !method_exists($order, 'getAllItems')) {
            return [];
        }

        $items = [];
        $seen = [];
        foreach ($order->getAllItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $identifier = $this->getOrderItemProductIdentifier($item);
            if ($identifier === '' || isset($seen[$identifier])) {
                continue;
            }

            $seen[$identifier] = true;
            $items[] = ['product_id' => $identifier];
        }

        return $items;
    }

    private function getOrderItemProductIdentifier(OrderItemInterface $item): string
    {
        $source = $this->getProductIdentifierSource();
        if ($source === 'entity_id') {
            return $this->normalizeProductId((string)$item->getProductId());
        }

        if ($source === 'sku') {
            return $this->normalizeProductId((string)$item->getSku());
        }

        try {
            $product = $this->productRepository->getById((int)$item->getProductId());
        } catch (NoSuchEntityException) {
            return '';
        }

        $value = $product->getData($source);
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return '';
        }

        return $this->normalizeProductId((string)$value);
    }

    private function getProductIdentifierSource(): string
    {
        $source = trim((string)$this->scopeConfig->getValue(self::XML_PATH_PRODUCT_IDENTIFIER, ScopeInterface::SCOPE_STORE));
        if ($source === '') {
            return 'entity_id';
        }

        return preg_match('/^[a-z][a-z0-9_]{0,254}$/', $source) === 1 ? $source : 'entity_id';
    }

    private function normalizeProductId(string $productId): string
    {
        $productId = trim($productId);
        if ($productId === '' || strlen($productId) > 190 || str_contains($productId, "\0")) {
            return '';
        }

        return $productId;
    }

    private function formatAmount(float $amount): string
    {
        return number_format(max(0.0, $amount), 2, '.', '');
    }
}
