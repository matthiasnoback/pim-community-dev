<?php

namespace Pim\Component\Catalog\Job;

use Akeneo\Component\Batch\Model\StepExecution;
use Doctrine\ORM\EntityRepository;
use Pim\Component\Catalog\FamilyVariant\ChangeFamilyVariantStructureOnDescendants;
use Pim\Component\Connector\Step\TaskletInterface;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ComputeVariantStructureChangesTasklet implements TaskletInterface
{
    /** @var StepExecution */
    private $stepExecution;

    /** @var EntityRepository */
    private $familyVariantRepository;

    /** @var ChangeFamilyVariantStructureOnDescendants */
    private $changeStructure;

    /**
     * @param EntityRepository                          $familyVariantRepository
     * @param ChangeFamilyVariantStructureOnDescendants $changeStructure
     */
    public function __construct(
        EntityRepository $familyVariantRepository,
        ChangeFamilyVariantStructureOnDescendants $changeStructure
    ) {
        $this->familyVariantRepository = $familyVariantRepository;
        $this->changeStructure = $changeStructure;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $jobParameters = $this->stepExecution->getJobParameters();
        $familyVariantCodes = $jobParameters->get('family_variant_codes');
        $familyVariants = $this->familyVariantRepository->findBy(['code' => $familyVariantCodes]);

        foreach ($familyVariants as $familyVariant) {
            $this->changeStructure->changeStructure($familyVariant);
        }
    }
}
