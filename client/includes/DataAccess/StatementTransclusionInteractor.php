<?php

namespace Wikibase\Client\DataAccess;

use Language;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * Renders the main Snaks associated with a given Property on an Entity.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class StatementTransclusionInteractor {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var PropertyIdResolver
	 */
	private $propertyIdResolver;

	/**
	 * @var SnaksFinder
	 */
	private $snaksFinder;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param Language $language
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param SnakFormatter $snakFormatter
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulator $usageAccumulator
	 */
	public function __construct(
		Language $language,
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		SnakFormatter $snakFormatter,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator
	) {
		$this->language = $language;
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->snakFormatter = $snakFormatter;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 * @param int[]|null $acceptableRanks
	 *
	 * @throws PropertyLabelNotResolvedException
	 * @return string
	 */
	public function render(
		EntityId $entityId,
		$propertyLabelOrId,
		array $acceptableRanks = null
	) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			// Continue as if nothing happened (for usage tracking purposes).
			$entity = null;
		}

		if ( $entity && !( $entity instanceof StatementListProvider ) ) {
			// For entities that can't have Statements, we don't need to track usage,
			// so just bail out.
			return '';
		}

		$this->usageAccumulator->addOtherUsage( $entityId );

		if ( $entity === null ) {
			return '';
		}

		$propertyId = $this->propertyIdResolver->resolvePropertyId(
			$propertyLabelOrId,
			$this->language->getCode()
		);

		$snaks = $this->snaksFinder->findSnaks(
			$entity,
			$propertyId,
			$acceptableRanks
		);

		return $this->formatSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string wikitext
	 */
	private function formatSnaks( array $snaks ) {
		$formattedValues = array();

		foreach ( $snaks as $snak ) {
			$formattedValue = $this->snakFormatter->formatSnak( $snak );

			if ( $formattedValue !== '' ) {
				$formattedValues[] = $formattedValue;
			}
		}

		$commaList = $this->language->commaList( $formattedValues );

		if ( $commaList === ''
			|| $this->snakFormatter->getFormat() === SnakFormatter::FORMAT_PLAIN
		) {
			return $commaList;
		}

		return "<span>$commaList</span>";
	}

}
