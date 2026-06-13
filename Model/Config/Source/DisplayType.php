<?php

declare(strict_types=1);

namespace BerryPath\Flow\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Data\OptionSourceInterface;

class DisplayType extends AbstractSource implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function getAllOptions(): array
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 'inline', 'label' => __('Inline')],
                ['value' => 'popup', 'label' => __('Popup')],
                ['value' => 'sidebar', 'label' => __('Sidebar')],
            ];
        }

        return $this->_options;
    }

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return $this->getAllOptions();
    }
}
