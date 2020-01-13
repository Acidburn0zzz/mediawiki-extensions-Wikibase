<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikimedia\Assert\Assert;

/**
 * This is a prefetching lookup that is aware of the migration plan of old to new term stores
 * and encloses the business logic of selecting the right store to fetch items terms from based
 * on where those items fall in the different ranges of migration stages.
 *
 * @license GPL-2.0-or-later
 */
class TermStoresDelegatingPrefetchingItemTermLookup implements PrefetchingTermLookup {
	/** @var DataAccessSettings */
	private $dataAccessSettings;
	/** @var PrefetchingTermLookup */
	private $normalizedStorePrefetchingTermLookup;
	/** @var PrefetchingTermLookup */
	private $wbTermsStorePrefetchingTermLookup;

	public function __construct(
		DataAccessSettings $dataAccessSettings, PrefetchingTermLookup $normalizedStorePrefetchingTermLookup,
		PrefetchingTermLookup $wbTermsStorePrefetchingTermLookup
	) {
		$this->dataAccessSettings = $dataAccessSettings;
		$this->normalizedStorePrefetchingTermLookup = $normalizedStorePrefetchingTermLookup;
		$this->wbTermsStorePrefetchingTermLookup = $wbTermsStorePrefetchingTermLookup;
	}

	/**
	 * @param ItemId[] $entityIds
	 *
	 * @return array of two arrays:
	 *    [
	 *        [ entity id to fetch from new store, ... ],
	 *        [ entity id to fetch from old store, ... ]
	 *    ]
	 */
	private function splitIdsPerTargetTermsStore( array $entityIds ): array {
		$normalizedStoreIds = [];

		foreach ( $entityIds as $i => $entityId ) {
			if ( $this->dataAccessSettings->useNormalizedItemTerms( $entityId->getNumericId() ) ) {
				$normalizedStoreIds[] = $entityId;
				unset( $entityIds[$i] );
			}
		}

		return [ $normalizedStoreIds, array_values( $entityIds ) ];
	}

	/**
	 * Loads a set of terms into the buffer.
	 * The source from which to fetch would typically be supplied to the buffer's constructor.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[]|null $termTypes The desired term types; null means all.
	 * @param string[]|null $languageCodes The desired languages; null means all.
	 */
	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		Assert::parameterElementType( ItemId::class, $entityIds, '$entityIds' );

		'@phan-var ItemId[] $entityIds';
		list( $normalizedStoreIds, $wbTermsStoreIds ) = $this->splitIdsPerTargetTermsStore( $entityIds );

		if ( $normalizedStoreIds !== [] ) {
			$this->normalizedStorePrefetchingTermLookup->prefetchTerms( $normalizedStoreIds, $termTypes, $languageCodes );
		}

		if ( $wbTermsStoreIds !== [] ) {
			$this->wbTermsStorePrefetchingTermLookup->prefetchTerms( $wbTermsStoreIds, $termTypes, $languageCodes );
		}
	}

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false of that term is known to not exist,
	 *         or null if the term was not yet requested via prefetchTerms().
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		if ( !$entityId instanceof ItemId ) {
			throw new InvalidArgumentException( '$entityId can only be ItemId' );
		}

		if ( $this->dataAccessSettings->useNormalizedItemTerms( $entityId->getNumericId() ) ) {
			return $this->normalizedStorePrefetchingTermLookup->getPrefetchedTerm( $entityId, $termType, $languageCode );
		} else {
			return $this->wbTermsStorePrefetchingTermLookup->getPrefetchedTerm( $entityId, $termType, $languageCode );
		}
	}

	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->invokeAppropriateStoreMethod( $entityId, 'getLabel', [ $entityId, $languageCode ] );
	}

	public function getLabels( EntityId $entityId, array $languageCodes ) {
		return $this->invokeAppropriateStoreMethod( $entityId, 'getLabels', [ $entityId, $languageCodes ] );
	}

	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->invokeAppropriateStoreMethod( $entityId, 'getDescription', [ $entityId, $languageCode ] );
	}

	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		return $this->invokeAppropriateStoreMethod( $entityId, 'getDescriptions', [ $entityId, $languageCodes ] );
	}

	private function invokeAppropriateStoreMethod( EntityId $entityId, $method, $params ) {
		if ( !$entityId instanceof ItemId ) {
			throw new InvalidArgumentException( '$entityId can only be ItemId' );
		}

		$targetLookup = $this->wbTermsStorePrefetchingTermLookup;
		if ( $this->dataAccessSettings->useNormalizedItemTerms( $entityId->getNumericId() ) ) {
			$targetLookup = $this->normalizedStorePrefetchingTermLookup;
		}

		return call_user_func_array( [ $targetLookup, $method ], $params );
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		if ( !$entityId instanceof ItemId ) {
			throw new InvalidArgumentException( '$entityId can only be ItemId' );
		}

		if ( $this->dataAccessSettings->useNormalizedItemTerms( $entityId->getNumericId() ) ) {
			return $this->normalizedStorePrefetchingTermLookup->getPrefetchedAliases( $entityId, $languageCode );
		} else {
			return $this->wbTermsStorePrefetchingTermLookup->getPrefetchedAliases( $entityId, $languageCode );
		}
	}
}
