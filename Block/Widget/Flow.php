<?php

declare(strict_types=1);

namespace BerryPath\Flow\Block\Widget;

use BerryPath\Flow\Block\Embed;
use Magento\Widget\Block\BlockInterface;

class Flow extends Embed implements BlockInterface
{
    protected $_template = 'BerryPath_Flow::widget.phtml';
}
