<?php

declare(strict_types=1);

namespace BerryPath\Flow\Block\Adminhtml\Render;

use BerryPath\Flow\Model\Feed\Config as FeedConfig;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Feeds extends Field
{
    protected $_template = 'BerryPath_Flow::system/config/fieldset/feeds.phtml';

    public function __construct(
        Context $context,
        private readonly FeedConfig $feedConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCacheLifetime(): ?int
    {
        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeedData(): array
    {
        return $this->feedConfig->getStoreFeedData();
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
