<?php

declare(strict_types=1);

namespace BerryPath\Flow\Model\Feed;

use BerryPath\Flow\Model\Config\Source\LocaleCode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    public const XML_PATH_GENERAL_ENABLED = 'berrypath_flow/general/enabled';
    public const XML_PATH_MARKET_CODE = 'berrypath_flow/general/market_code';
    public const XML_PATH_LOCALE_CODE = 'berrypath_flow/general/locale_code';
    public const XML_PATH_PRODUCT_IDENTIFIER = 'berrypath_flow/product/product_identifier';
    public const XML_PATH_FEED_ENABLED = 'berrypath_flow/feed/enabled';
    public const XML_PATH_FEED_INCLUDE_NOT_VISIBLE = 'berrypath_flow/feed/include_not_visible';
    public const XML_PATH_FEED_EXTRA_ATTRIBUTES = 'berrypath_flow/feed/extra_attributes';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isFeedEnabled(int $storeId): bool
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (NoSuchEntityException) {
            return false;
        }

        if (!$store->isActive()) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)
            && $this->scopeConfig->isSetFlag(self::XML_PATH_FEED_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function includeNotVisibleProducts(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEED_INCLUDE_NOT_VISIBLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getProductIdentifierSource(int $storeId): string
    {
        $source = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_IDENTIFIER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($source === '') {
            return 'entity_id';
        }

        return preg_match('/^[a-z][a-z0-9_]{0,254}$/', $source) === 1 ? $source : 'entity_id';
    }

    public function getMarketCode(int $storeId): string
    {
        $marketCode = (string)$this->scopeConfig->getValue(self::XML_PATH_MARKET_CODE, ScopeInterface::SCOPE_STORE, $storeId);
        $marketCode = strtolower(trim(str_replace('_', '-', $marketCode)));
        $marketCode = (string)preg_replace('/[^a-z0-9-]+/', '-', $marketCode);
        $marketCode = trim((string)preg_replace('/-+/', '-', $marketCode), '-');

        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $marketCode) === 1 ? $marketCode : '';
    }

    public function getLocaleCode(int $storeId): string
    {
        $locale = (string)$this->scopeConfig->getValue(self::XML_PATH_LOCALE_CODE, ScopeInterface::SCOPE_STORE, $storeId);
        if ($locale === '') {
            $locale = (string)$this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
        }

        return LocaleCode::normalizeLocaleCode($locale);
    }

    /**
     * @return array<int, string>
     */
    public function getExtraAttributeCodes(int $storeId): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_FEED_EXTRA_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $codes = preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $codes = array_filter(
            $codes,
            static fn (string $code): bool => preg_match('/^[a-z][a-z0-9_]{0,254}$/', $code) === 1
                && !DefaultAttributeCodes::contains($code)
        );

        return array_values(array_unique($codes));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStoreFeedData(): array
    {
        $feedData = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();

            $feedData[] = [
                'store_id' => $storeId,
                'code' => (string)$store->getCode(),
                'name' => (string)$store->getName(),
                'is_active' => (bool)$store->isActive(),
                'market_code' => $this->getMarketCode($storeId),
                'preview_url' => $this->getPreviewUrl($storeId),
                'feed_url' => $this->getFeedUrl($storeId),
            ];
        }

        return $feedData;
    }

    public function getFeedUrl(int $storeId): string
    {
        return $this->getStoreBaseUrl($storeId) . sprintf(
            'berrypath/feed/id/%d',
            $storeId
        );
    }

    public function getPreviewUrl(int $storeId): string
    {
        return $this->getStoreBaseUrl($storeId) . sprintf(
            'berrypath/feed/preview/id/%d/no-cache/%d',
            $storeId,
            time()
        );
    }

    private function getStoreBaseUrl(int $storeId): string
    {
        return rtrim($this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB), '/') . '/';
    }
}
