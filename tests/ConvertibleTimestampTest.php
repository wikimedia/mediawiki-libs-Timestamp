<?php
/**
 * Copyright (C) 2016 Tyler Romeo <tylerromeo@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Tyler Romeo <tylerromeo@gmail.com>
 */

namespace Wikimedia\Timestamp\Test;

use Closure;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class ConvertibleTimestampTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::__construct
	 */
	public function testConstructWithoutTimestamp() {
		$timestamp = new ConvertibleTimestamp();
		$this->assertInternalType( 'string', $timestamp->getTimestamp() );
		$this->assertNotEmpty( $timestamp->getTimestamp() );
		$this->assertNotEquals( false, strtotime( $timestamp->getTimestamp( TS_MW ) ) );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::__construct
	 */
	public function testConstructWithDateTime() {
		$input = '1343761268';
		$dt = new \DateTime( "@$input", new \DateTimeZone( 'GMT' ) );
		$timestamp = new ConvertibleTimestamp( $dt );
		$this->assertSame( $input, $timestamp->getTimestamp() );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::__toString
	 */
	public function testToString() {
		$timestamp = new ConvertibleTimestamp( '1406833268' ); // Equivalent to 20140731190108
		$this->assertEquals( '1406833268', $timestamp->__toString() );
	}

	public static function provideDiff() {
		return [
			[ '1406833268', '1406833269', '00 00 00 01' ],
			[ '1406833268', '1406833329', '00 00 01 01' ],
			[ '1406833268', '1406836929', '00 01 01 01' ],
			[ '1406833268', '1406923329', '01 01 01 01' ],
		];
	}

	/**
	 * @dataProvider provideDiff
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::diff
	 */
	public function testDiff( $timestamp1, $timestamp2, $expected ) {
		$timestamp1 = new ConvertibleTimestamp( $timestamp1 );
		$timestamp2 = new ConvertibleTimestamp( $timestamp2 );
		$diff = $timestamp1->diff( $timestamp2 );
		$this->assertEquals( $expected, $diff->format( '%D %H %I %S' ) );
	}

	/**
	 * Parse valid timestamps and output in MW format.
	 *
	 * @dataProvider provideValidTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::getTimestamp
	 */
	public function testValidParse( $originalFormat, $original, $expectedFormat, $expected ) {
		$timestamp = new ConvertibleTimestamp( $original );
		$this->assertEquals( $expected, $timestamp->getTimestamp( $expectedFormat ) );
	}

	public static function provideParseOnly() {
		// Formats supported only by setTimestamp(), but do not
		// have a constant for getTimestamp()
		return [
			'RFC 850' => [ 'Tuesday, 31-Jul-12 19:01:08 UTC', '20120731190108' ],
			'asctime' => [ 'Tue Jul 31 19:01:08 2012', '20120731190108' ],
			'old TS_POSTGRES' => [ '2012-07-31 19:01:08 GMT', '20120731190108' ],
		];
	}

	/**
	 * @dataProvider provideParseOnly
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testValidParseOnly( $original, $expected ) {
		$timestamp = new ConvertibleTimestamp( $original );
		$this->assertEquals( $expected, $timestamp->getTimestamp( TS_MW ) );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testValidParseZero() {
		$now = time();
		$timestamp = new ConvertibleTimestamp( 0 );
		$this->assertEquals(
			$now,
			$timestamp->getTimestamp( TS_UNIX ),
			'now',
			10.0 // acceptable delta in seconds
		);
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::now
	 */
	public function testNow() {
		$this->assertEquals(
			time(),
			ConvertibleTimestamp::now( TS_UNIX ),
			'now',
			10.0 // acceptable delta in seconds
		);
	}

	/**
	 * Parse invalid timestamps.
	 *
	 * @dataProvider provideInvalidTimestamps
	 * @expectedException \Wikimedia\Timestamp\TimestampException
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testInvalidParse( $input ) {
		new ConvertibleTimestamp( $input );
	}

	/**
	 * Output valid timestamps in different formats.
	 *
	 * @dataProvider provideValidTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::getTimestamp
	 */
	public function testValidFormats( $expectedFormat, $expected, $originalFormat, $original ) {
		$timestamp = new ConvertibleTimestamp( $original );
		$this->assertEquals( $expected, (string)$timestamp->getTimestamp( $expectedFormat ) );
	}

	/**
	 * @dataProvider provideValidTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::convert
	 */
	public function testConvert( $expectedFormat, $expected, $originalFormat, $original ) {
		$this->assertSame( $expected, ConvertibleTimestamp::convert( $expectedFormat, $original ) );
	}

	/**
	 * Format an invalid timestamp.
	 *
	 * @dataProvider provideInvalidTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::convert
	 */
	public function testConvertInvalid( $input ) {
		$this->assertSame( false, ConvertibleTimestamp::convert( TS_UNIX, $input ) );
	}

	/**
	 * Test an out-of-range timestamp.
	 *
	 * @dataProvider provideOutOfRangeTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp
	 * @expectedException \Wikimedia\Timestamp\TimestampException
	 */
	public function testOutOfRangeTimestamps( $format, $input ) {
		$timestamp = new ConvertibleTimestamp( $input );
		$timestamp->getTimestamp( $format );
	}

	public static function provideInvalidFormats() {
		return [
			[ 'Not a format' ],
			[ 98 ],
		];
	}

	/**
	 * @dataProvider provideInvalidFormats
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::getTimestamp
	 * @expectedException \Wikimedia\Timestamp\TimestampException
	 */
	public function testInvalidFormat( $format ) {
		$timestamp = new ConvertibleTimestamp( '1343761268' );
		$timestamp->getTimestamp( $format );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimezone
	 */
	public function testSetTimezone() {
		$timestamp = new ConvertibleTimestamp( 0 );
		$this->assertSame( null, $timestamp->setTimezone( 'GMT' ) );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimezone
	 * @expectedException \Wikimedia\Timestamp\TimestampException
	 */
	public function testSetTimezoneInvalid() {
		$timestamp = new ConvertibleTimestamp( 0 );
		$timestamp->setTimezone( 'Invalid' );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::getTimezone
	 */
	public function testGetTimezone() {
		$timestamp = new ConvertibleTimestamp( 0 );
		$this->assertInstanceOf(
			\DateTimeZone::class,
			$timestamp->getTimezone()
		);
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::format
	 */
	public function testFormat() {
		$timestamp = new ConvertibleTimestamp( '1343761268' );
		$this->assertSame( '1343761268', $timestamp->format( 'U' ) );
		$this->assertSame( '20120731190108', $timestamp->format( 'YmdHis' ) );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setFakeTime
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::time
	 */
	public function testFakeTime() {
		// fake clock ticks up
		$fakeTime = ConvertibleTimestamp::convert( TS_UNIX, '20010101000000' );
		ConvertibleTimestamp::setFakeTime( function () use ( &$fakeTime ) {
			return $fakeTime++;
		} );
		$this->assertSame( '20010101000000', ConvertibleTimestamp::now() );
		$this->assertSame( '20010101000001', ConvertibleTimestamp::convert( TS_MW, false ) );
		$this->assertSame( '20010101000002', ConvertibleTimestamp::now() );

		// fake time stays put
		$old = ConvertibleTimestamp::setFakeTime( '20200202112233' );
		$this->assertTrue( is_callable( $old ) );

		$this->assertSame( '20200202112233', ConvertibleTimestamp::now() );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::convert( TS_MW, false ) );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::now() );

		// no more fake time
		$old = ConvertibleTimestamp::setFakeTime( false );
		$this->assertInstanceOf( Closure::class, $old );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::convert( TS_MW, $old() ) );

		$this->assertNotSame( '20200202112233', ConvertibleTimestamp::now() );
	}

	/**
	 * Returns a list of valid timestamps in the format:
	 * [ type, timestamp_of_type, timestamp_in_MW ]
	 */
	public static function provideValidTimestamps() {
		return [
			// Formats supported in both directions
			[ TS_UNIX, '1343761268', TS_MW, '20120731190108' ],
			[ TS_UNIX_MICRO, '1343761268.000000', TS_MW, '20120731190108' ],
			[ TS_UNIX_MICRO, '1343761268.123456', TS_ORACLE, '31-07-2012 19:01:08.123456' ],
			[ TS_MW, '20120731190108', TS_MW, '20120731190108' ],
			[ TS_DB, '2012-07-31 19:01:08', TS_MW, '20120731190108' ],
			[ TS_ISO_8601, '2012-07-31T19:01:08Z', TS_MW, '20120731190108' ],
			[ TS_ISO_8601_BASIC, '20120731T190108Z', TS_MW, '20120731190108' ],
			[ TS_EXIF, '2012:07:31 19:01:08', TS_MW, '20120731190108' ],
			[ TS_RFC2822, 'Tue, 31 Jul 2012 19:01:08 GMT', TS_MW, '20120731190108' ],
			[ TS_ORACLE, '31-07-2012 19:01:08.000000', TS_MW, '20120731190108' ],
			[ TS_ORACLE, '31-07-2012 19:01:08.123456', TS_UNIX_MICRO, '1343761268.123456' ],
			[ TS_POSTGRES, '2012-07-31 19:01:08+00', TS_MW, '20120731190108' ],
			// Some extremes and weird values
			[ TS_ISO_8601, '9999-12-31T23:59:59Z', TS_MW, '99991231235959' ],
			[ TS_UNIX, '-62135596801', TS_MW, '00001231235959' ]
		];
	}

	/**
	 * List of invalid timestamps
	 */
	public static function provideInvalidTimestamps() {
		return [
			// Not matching any known patterns
			// (throws from main 'else' branch in setTimestamp)
			[ 'Not a timestamp' ],
			[ '1971:01:01 06:19:385' ],
			// Invalid values for known patterns
			// (throws from DateTime construction)
			[ 'Zed, 40 Mud 2012 99:99:99 GMT' ],
		];
	}

	/**
	 * Returns a list of out of range timestamps in the format:
	 * [ type, timestamp_of_type ]
	 */
	public static function provideOutOfRangeTimestamps() {
		return [
			// Various formats
			[ TS_MW, '-62167219201' ], // -0001-12-31T23:59:59Z
			[ TS_MW, '253402300800' ], // 10000-01-01T00:00:00Z
		];
	}
}
