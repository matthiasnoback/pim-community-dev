<?php

namespace Pim\Component\Catalog\FamilyVariant;

use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Pim\Component\Catalog\Model\EntityWithFamilyVariantInterface;
use Pim\Component\Catalog\Model\FamilyVariantInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Catalog\Model\VariantAttributeSetInterface;
use Pim\Component\Catalog\Repository\ProductModelRepositoryInterface;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ChangeFamilyVariantStructureOnDescendants
{
    /** @var ObjectRepository */
    private $variantProductRepository;

    /** @var ObjectRepository */
    private $productModelRepository;

    /** @var SaverInterface */
    private $productSaver;

    /** @var SaverInterface */
    private $productModelSaver;

    /**
     * @param ObjectRepository                $variantProductRepository
     * @param ProductModelRepositoryInterface $productModelRepository
     * @param SaverInterface                  $productSaver
     * @param SaverInterface                  $productModelSaver
     */
    public function __construct(
        ObjectRepository $variantProductRepository,
        ProductModelRepositoryInterface $productModelRepository,
        SaverInterface $productSaver,
        SaverInterface $productModelSaver
    ) {
        $this->variantProductRepository = $variantProductRepository;
        $this->productModelRepository = $productModelRepository;
        $this->productSaver = $productSaver;
        $this->productModelSaver = $productModelSaver;
    }

    /**
     * @param FamilyVariantInterface $familyVariant
     */
    public function changeStructure(FamilyVariantInterface $familyVariant): void
    {
        $attributeSets = $familyVariant->getVariantAttributeSets()->toArray();

        usort($attributeSets, function (
            VariantAttributeSetInterface $a,
            VariantAttributeSetInterface $b
        ) {
            if ($a->getLevel() === $b->getLevel()) {
                return 0;
            }

            return ($a->getLevel() > $b->getLevel()) ? -1 : 1;
        });

        foreach ($attributeSets as $attributeSet) {
            if ($attributeSet->getLevel() === $familyVariant->getNumberOfLevel()) {
                $entities = $this->variantProductRepository
                    ->findBy(['familyVariant' => $familyVariant]);
            } else {
                $entities = $this->productModelRepository
                    ->findSubProductModels($familyVariant);
            }

            foreach ($entities as $entity) {
                $this->removeExtraAttributes($attributeSet, $entity);

                if ($entity instanceof ProductModelInterface) {
                    $this->productModelSaver->save($entity);
                } else {
                    $this->productSaver->save($entity);
                }
            }
        }
    }

    /**
     * @param VariantAttributeSetInterface     $attributeSet
     * @param EntityWithFamilyVariantInterface $entity
     */
    private function removeExtraAttributes(
        VariantAttributeSetInterface $attributeSet,
        EntityWithFamilyVariantInterface $entity
    ): void {
        $valuesForVariation = $entity->getValuesForVariation();

        $extraAttributes = array_diff(
            $valuesForVariation->getAttributes(),
            $attributeSet->getAttributes()->toArray()
        );

        $productValues = $entity->getValues();
        foreach ($extraAttributes as $attribute) {
            $productValues->removeByAttribute($attribute);
        }

        $entity->setValues($productValues);
    }
}
