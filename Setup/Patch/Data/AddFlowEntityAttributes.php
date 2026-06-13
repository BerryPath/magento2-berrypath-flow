<?php

declare(strict_types=1);

namespace BerryPath\Flow\Setup\Patch\Data;

use BerryPath\Flow\Model\Config\Source\DisplayType;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddFlowEntityAttributes implements DataPatchInterface
{
    private const GROUP_NAME = 'BerryPath Flow';

    private const ATTR_PUBLIC_TOKEN = 'berrypath_flow_public_token';
    private const ATTR_DISPLAY_TYPE = 'berrypath_flow_display_type';
    private const ATTR_BUTTON_LABEL = 'berrypath_flow_button_label';
    private const ATTR_BANNER_TITLE = 'berrypath_flow_banner_title';
    private const ATTR_BANNER_DESCRIPTION = 'berrypath_flow_banner_description';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->addCategoryAttributes($eavSetup);
        $this->addProductAttributes($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @return array<class-string<DataPatchInterface>>
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public function getAliases(): array
    {
        return [];
    }

    private function addCategoryAttributes(EavSetup $eavSetup): void
    {
        $this->ensureAttributeGroup($eavSetup, Category::ENTITY, 11);

        foreach ($this->categoryAttributes() as $code => $config) {
            $this->addAttribute($eavSetup, Category::ENTITY, $code, $config);
        }
    }

    private function addProductAttributes(EavSetup $eavSetup): void
    {
        foreach ($this->productAttributes() as $code => $config) {
            $this->addAttribute($eavSetup, Product::ENTITY, $code, $config);
        }

        $this->assignProductAttributesToAllSets($eavSetup, [
            self::ATTR_PUBLIC_TOKEN => 10,
            self::ATTR_DISPLAY_TYPE => 20,
            self::ATTR_BUTTON_LABEL => 30,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function categoryAttributes(): array
    {
        return [
            self::ATTR_PUBLIC_TOKEN => $this->attributeConfig(
                'varchar',
                'Flow UUID',
                'text',
                10,
                'Paste the public token, share URL or embed URL from BerryPath.'
            ),
            self::ATTR_DISPLAY_TYPE => $this->attributeConfig(
                'varchar',
                'Display type',
                'select',
                20,
                null,
                ['source' => DisplayType::class, 'default' => 'popup']
            ),
            self::ATTR_BANNER_TITLE => $this->attributeConfig(
                'varchar',
                'Banner title',
                'text',
                30,
                'Shown above the category Flow widget button.'
            ),
            self::ATTR_BANNER_DESCRIPTION => $this->attributeConfig(
                'text',
                'Banner description',
                'textarea',
                40,
                'Shown above the category Flow widget button.'
            ),
            self::ATTR_BUTTON_LABEL => $this->attributeConfig(
                'varchar',
                'Button label',
                'text',
                50,
                'Used for popup and sidebar display types.'
            ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function productAttributes(): array
    {
        return [
            self::ATTR_PUBLIC_TOKEN => $this->attributeConfig(
                'varchar',
                'Flow UUID',
                'text',
                10,
                'Paste the public token, share URL or embed URL from BerryPath.'
            ),
            self::ATTR_DISPLAY_TYPE => $this->attributeConfig(
                'varchar',
                'Display type',
                'select',
                20,
                null,
                ['source' => DisplayType::class, 'default' => 'popup']
            ),
            self::ATTR_BUTTON_LABEL => $this->attributeConfig(
                'varchar',
                'Button label',
                'text',
                30,
                'Used for popup and sidebar display types.'
            ),
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function attributeConfig(
        string $type,
        string $label,
        string $input,
        int $sortOrder,
        ?string $note = null,
        array $extra = []
    ): array {
        return array_merge([
            'type' => $type,
            'label' => $label,
            'input' => $input,
            'required' => false,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'group' => self::GROUP_NAME,
            'sort_order' => $sortOrder,
            'note' => $note,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
        ], $extra);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addAttribute(EavSetup $eavSetup, string $entityType, string $code, array $config): void
    {
        if ($eavSetup->getAttributeId($entityType, $code)) {
            return;
        }

        $eavSetup->addAttribute($entityType, $code, $config);
    }

    /**
     * @param array<string, int> $attributes
     */
    private function assignProductAttributesToAllSets(EavSetup $eavSetup, array $attributes): void
    {
        $entityTypeId = (int)$eavSetup->getEntityTypeId(Product::ENTITY);

        foreach ($eavSetup->getAllAttributeSetIds($entityTypeId) as $attributeSetId) {
            $attributeSetId = (int)$attributeSetId;
            $this->ensureAttributeGroup($eavSetup, Product::ENTITY, 62, $attributeSetId);

            foreach ($attributes as $attributeCode => $sortOrder) {
                $attribute = $eavSetup->getAttribute(Product::ENTITY, $attributeCode);
                $attributeId = isset($attribute['attribute_id']) ? (int)$attribute['attribute_id'] : 0;
                if ($attributeId === 0) {
                    continue;
                }

                $eavSetup->addAttributeToSet(
                    $entityTypeId,
                    $attributeSetId,
                    self::GROUP_NAME,
                    $attributeId,
                    $sortOrder
                );
            }
        }
    }

    private function ensureAttributeGroup(
        EavSetup $eavSetup,
        string $entityType,
        int $sortOrder,
        ?int $attributeSetId = null
    ): void {
        $entityTypeId = (int)$eavSetup->getEntityTypeId($entityType);
        $attributeSetIds = $attributeSetId !== null
            ? [$attributeSetId]
            : array_map('intval', $eavSetup->getAllAttributeSetIds($entityTypeId));

        foreach ($attributeSetIds as $setId) {
            try {
                $groupId = (int)$eavSetup->getAttributeGroupId($entityTypeId, $setId, self::GROUP_NAME);
            } catch (LocalizedException) {
                $groupId = 0;
            }

            if ($groupId === 0) {
                $eavSetup->addAttributeGroup($entityTypeId, $setId, self::GROUP_NAME, $sortOrder);
            }
        }
    }
}
