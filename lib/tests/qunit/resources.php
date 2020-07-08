<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/tests/qunit',
	];
	$packageFilesModuleBase = [
		'localBasePath' => dirname( dirname( __DIR__ ) ),
		'remoteExtPath' => 'Wikibase/repo/',
	];

	$modules = [
		'wikibase.Site.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.Site.tests.js',
			],
			'dependencies' => [
				'wikibase.Site',
			],
		],

		'wikibase.sites.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.sites.tests.js',
			],
			'dependencies' => [
				'wikibase',
				'wikibase.Site',
				'wikibase.sites',
			],
		],

		'wikibase.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.tests.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],
	];

	return $modules;
} );
