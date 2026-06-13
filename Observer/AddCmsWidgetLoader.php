<?php

declare(strict_types=1);

namespace BerryPath\Flow\Observer;

use BerryPath\Flow\Block\Embed;
use Magento\Cms\Block\Page as PageBlock;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class AddCmsWidgetLoader implements ObserverInterface
{
    public function __construct(
        private readonly PageConfig $pageConfig
    ) {
    }

    public function execute(Observer $observer): void
    {
        $page = $this->getPage($observer);
        if (!$page instanceof Page) {
            return;
        }

        $content = (string)$page->getContent();
        if (!$this->hasFlowWidgetDirective($content)) {
            return;
        }

        $this->pageConfig->addRemotePageAsset(
            Embed::LOADER_SCRIPT_URL,
            'js',
            ['attributes' => ['defer' => 'defer']],
            'berrypath-flow-loader'
        );
    }

    private function getPage(Observer $observer): ?Page
    {
        $page = $observer->getData('page');
        if ($page instanceof Page) {
            return $page;
        }

        $layout = $observer->getData('layout');
        if (!is_object($layout) || !method_exists($layout, 'getBlock')) {
            return null;
        }

        $block = $layout->getBlock('cms_page');
        if (!$block instanceof PageBlock) {
            return null;
        }

        $page = $block->getPage();

        return $page instanceof Page ? $page : null;
    }

    private function hasFlowWidgetDirective(string $content): bool
    {
        if ($content === '' || !str_contains($content, '{{widget')) {
            return false;
        }

        return str_contains($content, 'BerryPath\\Flow\\Block\\Widget\\Flow')
            || str_contains($content, 'BerryPath\\\\Flow\\\\Block\\\\Widget\\\\Flow')
            || str_contains($content, 'berrypath_flow_widget');
    }
}
