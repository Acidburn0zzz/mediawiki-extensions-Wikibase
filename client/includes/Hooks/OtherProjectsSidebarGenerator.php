<?php

namespace Wikibase\Client\Hooks;

use Hooks;
use LanguageCode;
use Site;
use SiteLookup;
use Title;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Outputs a sidebar section for other project links.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarGenerator {

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteLinksForDisplayLookup
	 */
	private $siteLinksForDisplayLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var SidebarLinkBadgeDisplay
	 */
	private $sidebarLinkBadgeDisplay;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var string[]
	 */
	private $siteIdsToOutput;

	/**
	 * @param string $localSiteId
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteLinksForDisplayLookup $siteLinksForDisplayLookup
	 * @param SiteLookup $siteLookup
	 * @param SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	 * @param UsageAccumulator $usageAccumulator
	 * @param string[] $siteIdsToOutput
	 */
	public function __construct(
		$localSiteId,
		SiteLinkLookup $siteLinkLookup,
		SiteLinksForDisplayLookup $siteLinksForDisplayLookup,
		SiteLookup $siteLookup,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay,
		UsageAccumulator $usageAccumulator,
		array $siteIdsToOutput
	) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLinksForDisplayLookup = $siteLinksForDisplayLookup;
		$this->siteLookup = $siteLookup;
		$this->sidebarLinkBadgeDisplay = $sidebarLinkBadgeDisplay;
		$this->usageAccumulator = $usageAccumulator;
		$this->siteIdsToOutput = $siteIdsToOutput;
	}

	/**
	 * @param Title $title
	 *
	 * @return array[] Array of arrays of attributes describing sidebar links, sorted by the site's
	 * group and global ids.
	 */
	public function buildProjectLinkSidebar( Title $title ) {
		$itemId = $this->siteLinkLookup->getItemIdForLink(
			$this->localSiteId,
			$title->getPrefixedText()
		);

		if ( !$itemId ) {
			return [];
		}

		return $this->buildProjectLinkSidebarFromItemId( $itemId );
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return array[] Array of arrays of attributes describing sidebar links, sorted by the site's
	 * group and global ids.
	 */
	public function buildProjectLinkSidebarFromItemId( ItemId $itemId ) {
		$sidebar = $this->buildPreliminarySidebarFromSiteLinks(
			$this->siteLinksForDisplayLookup->getSiteLinksForItemId( $itemId )
		);
		$sidebar = $this->runHook( $itemId, $sidebar );

		return $this->sortAndFlattenSidebar( $sidebar );
	}

	/**
	 * @param ItemId $itemId
	 * @param array $sidebar
	 *
	 * @return array
	 */
	private function runHook( ItemId $itemId, array $sidebar ) {
		$newSidebar = $sidebar;

		// Deprecated, use WikibaseClientSiteLinksForItem instead
		Hooks::run( 'WikibaseClientOtherProjectsSidebar', [
			$itemId, &$newSidebar, $this->siteIdsToOutput, $this->usageAccumulator
		] );

		if ( $newSidebar === $sidebar ) {
			return $sidebar;
		}

		// @phan-suppress-next-line PhanRedundantCondition Hook + pass-by-ref false positive
		if ( !is_array( $newSidebar ) || !$this->isValidSidebar( $newSidebar ) ) {
			wfLogWarning( 'Other projects sidebar data invalid after hook run.' );
			return $sidebar;
		}

		return $newSidebar;
	}

	/**
	 * @param array $sidebar
	 * @return bool
	 */
	private function isValidSidebar( array $sidebar ) {
		// Make sure all required array keys are set and are string.
		foreach ( $sidebar as $siteGroup => $perSiteGroup ) {
			if ( !is_string( $siteGroup ) || !is_array( $perSiteGroup ) ) {
				return false;
			}

			foreach ( $perSiteGroup as $siteId => $perSite ) {
				if ( !is_string( $siteId )
					|| !isset( $perSite['msg'] )
					|| !isset( $perSite['class'] )
					|| !isset( $perSite['href'] )
					|| !is_string( $perSite['msg'] )
					|| !is_string( $perSite['class'] )
					|| !is_string( $perSite['href'] )
				) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array[] Arrays of link attributes indexed by site group and by global site id.
	 */
	private function buildPreliminarySidebarFromSiteLinks( array $siteLinks ) {
		$linksByGroup = [];

		foreach ( $siteLinks as $siteLink ) {
			if ( !in_array( $siteLink->getSiteId(), $this->siteIdsToOutput ) ) {
				continue;
			}

			$site = $this->siteLookup->getSite( $siteLink->getSiteId() );

			if ( $site !== null ) {
				$group = $site->getGroup();
				$globalId = $site->getGlobalId();
				// Index by site group and global id
				$linksByGroup[$group][$globalId] = $this->buildSidebarLink( $siteLink, $site );
			}
		}

		return $linksByGroup;
	}

	/**
	 * The arrays of link attributes are indexed by site group and by global site id.
	 * Sort them by both and then return the flattened array.
	 *
	 * @param array[] $linksByGroup
	 *
	 * @return array[] Array of arrays of attributes describing sidebar links, sorted by the site's
	 * group and global ids.
	 */
	private function sortAndFlattenSidebar( array $linksByGroup ) {
		$result = [];

		ksort( $linksByGroup ); // Sort by group id

		foreach ( $linksByGroup as $linksPerGroup ) {
			ksort( $linksPerGroup ); // Sort individual arrays by global site id
			$result = array_merge( $result, array_values( $linksPerGroup ) );
		}

		return $result;
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string[] Array of attributes describing a sidebar link.
	 */
	private function buildSidebarLink( SiteLink $siteLink, Site $site ) {
		// Messages in the WikimediaMessages extension (as of 2015-03-31):
		// wikibase-otherprojects-commons
		// wikibase-otherprojects-testwikidata
		// wikibase-otherprojects-wikidata
		// wikibase-otherprojects-wikinews
		// wikibase-otherprojects-wikipedia
		// wikibase-otherprojects-wikiquote
		// wikibase-otherprojects-wikisource
		// wikibase-otherprojects-wikivoyage
		$attributes = [
			'msg' => 'wikibase-otherprojects-' . $site->getGroup(),
			'class' => 'wb-otherproject-link wb-otherproject-' . $site->getGroup(),
			'href' => $site->getPageUrl( $siteLink->getPageName() )
		];

		$siteLanguageCode = $site->getLanguageCode();
		if ( $siteLanguageCode !== null ) {
			$attributes['hreflang'] = LanguageCode::bcp47( $siteLanguageCode );
		}

		$this->sidebarLinkBadgeDisplay->applyBadgeToLink(
			$attributes,
			$this->sidebarLinkBadgeDisplay->getBadgeInfo( $siteLink->getBadges() )
		);

		return $attributes;
	}

}
