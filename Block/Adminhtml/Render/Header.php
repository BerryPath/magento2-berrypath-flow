<?php

declare(strict_types=1);

namespace BerryPath\Flow\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Header extends Field
{
    protected $_template = 'BerryPath_Flow::system/config/fieldset/header.phtml';

    public function render(AbstractElement $element): string
    {
        $element->addClass('berrypath');

        return $this->toHtml();
    }
}
