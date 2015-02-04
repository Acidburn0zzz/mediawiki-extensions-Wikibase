<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\TimeDetailsFormatter;

/**
 * @covers Wikibase\Lib\TimeDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class TimeDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new TimeDetailsFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en'
		) );

		return array(
			array(
				new TimeValue( '+2001-01-01T00:00:00Z', 60, 0, 1, 10, TimeFormatter::CALENDAR_GREGORIAN ),
				$options,
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*2001[^<>]*</h4>',
						'<td[^<>]*>\+0*2001-01-01T00:00:00Z</td>',
						'<td[^<>]*>60</td>',
						'<td[^<>]*>Gregorian</td>',
						'<td[^<>]*>10</td>',
						'<td[^<>]*>0</td>',
						'<td[^<>]*>1</td>',
					)
				) . '@s'
			),
			array(
				new TimeValue( '+2001-01-01T00:00:00Z', 60, 0, 1, 10, 'Stardate' ),
				$options,
				'@.*<td[^<>]*>Stardate</td>.*@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = new TimeDetailsFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

}
