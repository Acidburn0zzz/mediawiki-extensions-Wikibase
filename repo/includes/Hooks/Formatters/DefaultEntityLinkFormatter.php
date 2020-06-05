<?php

namespace Wikibase\Repo\Hooks\Formatters;

use HtmlArmor;
use Language;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityTitleTextLookup;

/**
 * Utility class to format entity links with labels for usage in hooks.
 */
class DefaultEntityLinkFormatter implements EntityLinkFormatter {

	/**
	 * @var Language
	 */
	private $pageLanguage;

	/**
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	public function __construct( Language $pageLanguage, EntityTitleTextLookup $entityTitleTextLookup ) {
		$this->pageLanguage = $pageLanguage;
		$this->entityTitleTextLookup = $entityTitleTextLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function getHtml( EntityId $entityId, array $labelData = null ) {
		/** @var Language $labelLang */
		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );

		$idHtml = '<span class="wb-itemlink-id">'
				  . wfMessage(
					  'wikibase-itemlink-id-wrapper',
					  $entityId->getSerialization()
				  )->inContentLanguage()->escaped()
				  . '</span>';

		$labelHtml = '<span class="wb-itemlink-label"'
					 . ' lang="' . htmlspecialchars( $labelLang->getHtmlCode() ) . '"'
					 . ' dir="' . htmlspecialchars( $labelLang->getDir() ) . '">'
					 . HtmlArmor::getHtml( $labelText )
					 . '</span>';

		return '<span class="wb-itemlink">'
			   . wfMessage( 'wikibase-itemlink' )->rawParams(
				$labelHtml,
				$idHtml
			)->inContentLanguage()->escaped()
			   . '</span>';
	}

	/**
	 * @param string[]|null $termData A term record as returned by
	 * LanguageFallbackChain::extractPreferredValueOrAny(),
	 * containing the 'value' and 'language' fields, or null
	 * or an empty array.
	 *
	 * @see LanguageFallbackChain::extractPreferredValueOrAny
	 *
	 * @return array list( string $text, Language $language )
	 */
	private function extractTextAndLanguage( array $termData = null ) {
		if ( $termData ) {
			return [
				$termData['value'],
				Language::factory( $termData['language'] )
			];
		} else {
			return [
				'',
				$this->pageLanguage
			];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTitleAttribute(
		$entityIdOrTitle,
		array $labelData = null,
		array $descriptionData = null
	) {
		/** @var Language $labelLang */
		/** @var Language $descriptionLang */

		list( $labelText, $labelLang ) = $this->extractTextAndLanguage( $labelData );
		list( $descriptionText, $descriptionLang ) = $this->extractTextAndLanguage( $descriptionData );

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		if ( $entityIdOrTitle instanceof EntityId ) {
			$titleText = ( $labelText !== '' )
				? $labelLang->getDirMark() . $labelText
				. $this->pageLanguage->getDirMark()
				: $this->entityTitleTextLookup->getPrefixedText( $entityIdOrTitle );
		} elseif ( $entityIdOrTitle instanceof Title ) {
			$titleText = ( $labelText !== '' )
				? $labelLang->getDirMark() . $labelText
				. $this->pageLanguage->getDirMark()
				: $entityIdOrTitle->getPrefixedText();
		} else {
			throw new \LogicException( '$entityIdOrTitle should have been Title or EntityId' );
		}

		if ( $descriptionText !== '' ) {
			$descriptionText = $descriptionLang->getDirMark() . $descriptionText
							   . $this->pageLanguage->getDirMark();
			return wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionText
			)->inContentLanguage()->text();
		} else {
			return $titleText; // no description, just display the title then
		}
	}

	public function getFragment( EntityId $entityId, $fragment ) {
		return $fragment;
	}

}
