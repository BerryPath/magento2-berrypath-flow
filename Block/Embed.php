<?php

declare(strict_types=1);

namespace BerryPath\Flow\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Embed extends Template
{
    private const ATTR_PUBLIC_TOKEN = 'berrypath_flow_public_token';
    private const ATTR_DISPLAY_TYPE = 'berrypath_flow_display_type';
    private const ATTR_BUTTON_LABEL = 'berrypath_flow_button_label';
    private const ATTR_BANNER_TITLE = 'berrypath_flow_banner_title';
    private const ATTR_BANNER_DESCRIPTION = 'berrypath_flow_banner_description';

    private const XML_PATH_CATEGORY_ENABLED = 'berrypath_flow/category/enabled';
    private const XML_PATH_PRODUCT_ENABLED = 'berrypath_flow/product/enabled';
    private const XML_PATH_MARKET_CODE = 'berrypath_flow/general/market_code';
    private const XML_PATH_PRODUCT_IDENTIFIER = 'berrypath_flow/product/product_identifier';

    private const EMBED_ORIGIN = 'https://app.berrypath.development:8443';

    protected $_template = 'BerryPath_Flow::widget.phtml';

    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        private readonly ResolverInterface $localeResolver,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function canRender(): bool
    {
        if ($this->isAutomaticPlacement() && !$this->isGlobalPlacementEnabled()) {
            return false;
        }

        return $this->getPublicToken() !== '';
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->canRender()) {
            $this->pageConfig->addRemotePageAsset(
                $this->getLoaderScriptUrl(),
                'js',
                ['attributes' => ['defer' => 'defer']],
                'berrypath-flow-loader'
            );
        }

        return $this;
    }

    public function isButtonMode(): bool
    {
        return $this->getDisplayType() !== 'inline';
    }

    /**
     * @return array<string, string>
     */
    public function getFlowAttributes(): array
    {
        $attributes = [
            'data-berrypath-flow' => $this->getPublicToken(),
            'data-berrypath-type' => $this->getDisplayType(),
        ];

        $locale = $this->getLocaleCode();
        if ($locale !== '') {
            $attributes['data-berrypath-locale'] = $locale;
        }

        $marketCode = $this->getMarketCode();
        if ($marketCode !== '') {
            $attributes['data-berrypath-market'] = $marketCode;
        }

        $productId = $this->getProductId();
        if ($productId !== '') {
            $attributes['data-berrypath-product-id'] = $productId;
        }

        return $attributes;
    }

    public function getLoaderScriptUrl(): string
    {
        return self::EMBED_ORIGIN . '/embed/berrypath.js';
    }

    public function getPublicToken(): string
    {
        $token = $this->stringData('public_token');
        if ($token !== '') {
            return $this->normalizeToken($token);
        }

        $context = $this->getFlowContext();
        if ($context !== 'category' && $context !== 'product') {
            return '';
        }

        $entityToken = $this->entityAttributeValue(self::ATTR_PUBLIC_TOKEN);
        if ($entityToken !== '') {
            return $this->normalizeToken($entityToken);
        }

        return '';
    }

    public function getDisplayType(): string
    {
        $displayType = $this->stringData('display_type');
        if ($displayType === '' && $this->isAutomaticPlacement()) {
            $displayType = $this->entityAttributeValue(self::ATTR_DISPLAY_TYPE);
        }

        return in_array($displayType, ['inline', 'popup', 'sidebar'], true) ? $displayType : 'popup';
    }

    public function getButtonLabel(): string
    {
        $label = $this->stringData('button_label');
        if ($label === '' && $this->isAutomaticPlacement()) {
            $label = $this->entityAttributeValue(self::ATTR_BUTTON_LABEL);
        }

        return $label !== '' ? $label : (string)__('Open advice flow');
    }

    public function getBannerTitle(): string
    {
        $title = $this->stringData('banner_title');
        if ($title === '' && $this->isAutomaticPlacement()) {
            $title = $this->entityAttributeValue(self::ATTR_BANNER_TITLE);
        }

        return $title !== '' ? $title : (string)__('Need help choosing?');
    }

    public function getBannerDescription(): string
    {
        $description = $this->stringData('banner_description');
        if ($description === '' && $this->isAutomaticPlacement()) {
            $description = $this->entityAttributeValue(self::ATTR_BANNER_DESCRIPTION);
        }

        return $description !== '' ? $description : (string)__('Answer a few short questions and quickly find the product that fits you.');
    }

    public function getCssClass(): string
    {
        return trim((string)preg_replace('/[^A-Za-z0-9 _-]/', '', $this->stringData('css_class')));
    }

    public function getBlockCssClass(): string
    {
        $classes = [
            'berrypath-flow-widget',
            'berrypath-flow-widget--' . $this->getFlowContext(),
            'berrypath-flow-widget--' . $this->getDisplayType(),
        ];

        $extraClass = $this->getCssClass();
        if ($extraClass !== '') {
            $classes[] = $extraClass;
        }

        return trim(implode(' ', $classes));
    }

    public function getWrapperCssClass(): string
    {
        return $this->getBlockCssClass();
    }

    public function getElementCssClass(string $element): string
    {
        $element = strtolower((string)preg_replace('/[^a-z0-9-]/i', '', $element));
        if (!in_array($element, ['button', 'mount'], true)) {
            return '';
        }

        return implode(' ', [
            'berrypath-flow-widget__' . $element,
            'berrypath-flow-widget__' . $element . '--' . $this->getFlowContext(),
            'berrypath-flow-widget__' . $element . '--' . $this->getDisplayType(),
        ]);
    }

    private function isAutomaticPlacement(): bool
    {
        return (bool)$this->getData('automatic_placement');
    }

    private function getFlowContext(): string
    {
        $context = $this->stringData('flow_context');
        if ($context === '') {
            $context = $this->stringData('context');
        }

        return in_array($context, ['category', 'product', 'generic'], true) ? $context : 'generic';
    }

    private function isGlobalPlacementEnabled(): bool
    {
        return match ($this->getFlowContext()) {
            'category' => $this->scopeConfig->isSetFlag(self::XML_PATH_CATEGORY_ENABLED, ScopeInterface::SCOPE_STORE),
            'product' => $this->scopeConfig->isSetFlag(self::XML_PATH_PRODUCT_ENABLED, ScopeInterface::SCOPE_STORE),
            default => true,
        };
    }

    private function entityAttributeValue(string $attributeCode): string
    {
        $entity = $this->currentEntity();
        if (!is_object($entity)) {
            return '';
        }

        if (method_exists($entity, 'getData')) {
            $value = $entity->getData($attributeCode);
            if ($value !== null && $value !== '') {
                return trim((string)$value);
            }
        }

        if (method_exists($entity, 'getCustomAttribute')) {
            $attribute = $entity->getCustomAttribute($attributeCode);
            if ($attribute !== null && method_exists($attribute, 'getValue')) {
                return trim((string)$attribute->getValue());
            }
        }

        return '';
    }

    private function currentEntity(): ?object
    {
        return match ($this->getFlowContext()) {
            'category' => $this->registry->registry('current_category'),
            'product' => $this->registry->registry('current_product'),
            default => null,
        };
    }

    private function getLocaleCode(): string
    {
        $locale = $this->stringData('locale');
        if ($locale === '') {
            $locale = $this->localeResolver->getLocale();
        }

        $locale = strtolower(str_replace('_', '-', trim($locale)));

        return preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8}){0,3}$/', $locale) === 1 ? $locale : '';
    }

    private function getMarketCode(): string
    {
        $marketCode = $this->stringData('market_code');
        if ($marketCode === '') {
            $marketCode = (string)$this->scopeConfig->getValue(self::XML_PATH_MARKET_CODE, ScopeInterface::SCOPE_STORE);
        }

        $marketCode = strtolower(trim(str_replace('_', '-', $marketCode)));
        $marketCode = (string)preg_replace('/[^a-z0-9-]+/', '-', $marketCode);
        $marketCode = trim((string)preg_replace('/-+/', '-', $marketCode), '-');

        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $marketCode) === 1 ? $marketCode : '';
    }

    private function getProductId(): string
    {
        $productId = $this->stringData('product_id');
        if ($productId !== '') {
            return $this->normalizeProductId($productId);
        }

        if ($this->getFlowContext() !== 'product') {
            return '';
        }

        $product = $this->registry->registry('current_product');
        if (!$product instanceof ProductInterface) {
            return '';
        }

        if ($this->getProductIdentifierSource() === 'entity_id') {
            return $this->normalizeProductId((string)$product->getId());
        }

        $value = $product->getData($this->getProductIdentifierSource());
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

    private function normalizeToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (preg_match('~/(?:embed|f)/([a-z0-9-]{8,80})(?:\.js)?(?:[/?#]|$)~i', $token, $matches) === 1) {
            $token = $matches[1];
        } elseif (preg_match('/^([a-z0-9-]{8,80})(?:\.js)?(?:\?.*)?$/i', $token, $matches) === 1) {
            $token = $matches[1];
        }

        return preg_match('/^[a-z0-9-]{8,80}$/i', $token) === 1 ? $token : '';
    }

    private function normalizeProductId(string $productId): string
    {
        $productId = trim($productId);
        if ($productId === '' || strlen($productId) > 190 || str_contains($productId, "\0")) {
            return '';
        }

        return $productId;
    }

    private function stringData(string $key): string
    {
        return trim((string)$this->getData($key));
    }
}
