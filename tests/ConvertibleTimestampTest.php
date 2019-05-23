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

		$timestamp = new ConvertibleTimestamp( '20140731190108' );
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
		$s = " \r\n \t\t\n \r ";
		return [
			// Systematically testing regexes
			'TS_DB' => [ '2012-07-31 19:01:08', '1343761268.000000' ],
			'TS_MW' => [ '20120731190108', '1343761268.000000' ],
			'TS_ISO_8601' => [ '2012-07-31T19:01:08Z', '1343761268.000000' ],
			'TS_ISO_8601, no Z' => [ '2012-07-31T19:01:08', '1343761268.000000' ],
			'TS_ISO_8601, milliseconds' => [ '2012-07-31T19:01:08.123Z', '1343761268.123000' ],
			'TS_ISO_8601, microseconds, no Z' => [ '2012-07-31T19:01:08.123456', '1343761268.123456' ],
			'TS_ISO_8601, microseconds with comma' => [ '2012-07-31T19:01:08,123456', '1343761268.123456' ],
			'TS_ISO_8601, timezone +0200' => [ '2012-07-31T21:01:08+0200', '1343761268.000000' ],
			'TS_ISO_8601, timezone -02:00' => [ '2012-07-31T17:01:08-02:00', '1343761268.000000' ],
			'TS_ISO_8601, timezone +04' => [ '2012-07-31T23:01:08.123+04', '1343761268.123000' ],
			'TS_ISO_8601, no T' => [ '2012-07-31 19:01:08Z', '1343761268.000000' ],
			'TS_ISO_8601, no T, no Z' => [ '2012-07-31 19:01:08', '1343761268.000000' ],
			'TS_ISO_8601, no T, milliseconds' => [ '2012-07-31 19:01:08.123Z', '1343761268.123000' ],
			'TS_ISO_8601, no T, timezone +0200' => [ '2012-07-31 21:01:08+0200', '1343761268.000000' ],
			'TS_ISO_8601, no T, timezone -02:00' => [ '2012-07-31 17:01:08-02:00', '1343761268.000000' ],
			'TS_ISO_8601, no T, timezone +04' => [ '2012-07-31 23:01:08.123+04', '1343761268.123000' ],
			'TS_ISO_8601_BASIC' => [ '20120731T190108Z', '1343761268.000000' ],
			'TS_ISO_8601_BASIC, no Z' => [ '20120731T190108', '1343761268.000000' ],
			'TS_ISO_8601_BASIC, milliseconds' => [ '20120731T190108.123Z', '1343761268.123000' ],
			'TS_ISO_8601_BASIC, microseconds, no Z' => [ '20120731T190108.123456', '1343761268.123456' ],
			'TS_ISO_8601_BASIC, microseconds w/comma' => [ '20120731T190108,123456', '1343761268.123456' ],
			'TS_ISO_8601_BASIC, timezone +0200' => [ '20120731T210108+0200', '1343761268.000000' ],
			'TS_ISO_8601_BASIC, timezone -02:00' => [ '20120731T170108-02:00', '1343761268.000000' ],
			'TS_ISO_8601_BASIC, timezone +04' => [ '20120731T230108.123+04', '1343761268.123000' ],
			'TS_UNIX' => [ '1343761268', '1343761268.000000' ],
			'TS_UNIX, negative' => [ '-1343761268', '-1343761268.000000' ],
			'TS_UNIX_MICRO' => [ '1343761268.123456', '1343761268.123456' ],
			'TS_UNIX_MICRO, lower precision' => [ '1343761268.123', '1343761268.123000' ],
			'TS_UNIX_MICRO, negative' => [ '-1343761268.123456', '-1343761268.123456' ],
			'TS_UNIX_MICRO, negative, low precision' => [ '-1343761268.123', '-1343761268.123000' ],
			'TS_UNIX_MICRO, near-zero microseconds' => [ '1343761268.000006', '1343761268.000006' ],
			'TS_ORACLE' => [ '31-07-2012 19:01:08.123456', '1343761268.123456' ],
			'TS_POSTGRES' => [ '2012-07-31 19:01:08+00', '1343761268.000000' ],
			'TS_POSTGRES, milliseconds' => [ '2012-07-31 19:01:08.123+00', '1343761268.123000' ],
			'TS_POSTGRES, microseconds' => [ '2012-07-31 19:01:08.123456+00', '1343761268.123456' ],
			'TS_POSTGRES, timezone +02' => [ '2012-07-31 21:01:08+02', '1343761268.000000' ],
			'TS_POSTGRES, timezone -02' => [ '2012-07-31 17:01:08-02', '1343761268.000000' ],
			'TS_POSTGRES, timezone  02' => [ '2012-07-31 21:01:08 02', '1343761268.000000' ],
			'old TS_POSTGRES' => [ '2012-07-31 19:01:08 GMT', '1343761268.000000' ],
			'old TS_POSTGRES, milliseconds' => [ '2012-07-31 19:01:08.123 GMT', '1343761268.123000' ],
			'old TS_POSTGRES, microseconds' => [ '2012-07-31 19:01:08.123456 GMT', '1343761268.123456' ],
			'TS_EXIF' => [ '2012-07-31 19:01:08', '1343761268.000000' ],
			'TS_RFC2822' => [ 'Tue, 31 Jul 2012 19:01:08 GMT', '1343761268.000000' ],
			'TS_RFC2822, odd spacing' => [
				"{$s}Tue,{$s}31{$s}Jul{$s}2012{$s}19{$s}:{$s}01{$s}:{$s}08{$s}GMT", '1343761268.000000'
			],
			'TS_RFC2822, minimal spacing' => [ 'Tue,31 Jul 2012 19:01:08 GMT', '1343761268.000000' ],
			'TS_RFC2822, no weekday' => [ '31 Jul 2012 19:01:08 GMT', '1343761268.000000' ],
			'TS_RFC2822, single-digit day' => [ 'Tue, 1 Jul 2012 19:01:08 GMT', '1341342068.000000' ],
			'TS_RFC2822, year "12" => 2012' => [ 'Tue, 31 Jul 12 19:01:08 GMT', '1343761268.000000' ],
			'TS_RFC2822, year "50" => 1950' => [ 'Tue, 31 Jul 50 19:01:08 GMT', '-612766732.000000' ],
			'TS_RFC2822, year "112" => 2012' => [ 'Tue, 31 Jul 112 19:01:08 GMT', '1343761268.000000' ],
			'TS_RFC2822, missing timezone' => [ 'Tue, 31 Jul 2012 19:01:08', '1343761268.000000' ],
			'TS_RFC2822, timezone UT' => [ 'Tue, 31 Jul 2012 19:01:08 UT', '1343761268.000000' ],
			'TS_RFC2822, timezone +0200' => [ 'Tue, 31 Jul 2012 21:01:08 +0200', '1343761268.000000' ],
			'TS_RFC2822, timezone -0200' => [ 'Tue, 31 Jul 2012 17:01:08 -0200', '1343761268.000000' ],
			'TS_RFC2822, timezone EDT' => [ 'Tue, 31 Jul 2012 15:01:08 EDT', '1343761268.000000' ],
			'TS_RFC2822, timezone A (ignored)' => [ 'Tue, 31 Jul 2012 19:01:08 A', '1343761268.000000' ],
			'TS_RFC2822, timezone n (ignored)' => [ 'Tue, 31 Jul 2012 19:01:08 n', '1343761268.000000' ],
			'TS_RFC2822, trailing comment' => [
				'Tue, 31 Jul 2012 19:01:08 GMT; a comment', '1343761268.000000'
			],
			'TS_RFC2822, trailing comment with space' => [
				"Tue, 31 Jul 2012 19:01:08 GMT{$s}; a comment", '1343761268.000000'
			],
			'TS_RFC850' => [ 'Tuesday, 31-Jul-12 19:01:08 UTC', '1343761268.000000' ],
			'TS_RFC850, no timezone' => [ 'Tuesday, 31-Jul-12 19:01:08', '1343761268.000000' ],
			'TS_RFC850, timezone +02' => [ 'Tuesday, 31-Jul-12 21:01:08 +02', '1343761268.000000' ],
			'TS_RFC850, timezone +0200' => [ 'Tuesday, 31-Jul-12 21:01:08 +0200', '1343761268.000000' ],
			'TS_RFC850, timezone +02:00' => [ 'Tuesday, 31-Jul-12 21:01:08 +02:00', '1343761268.000000' ],
			'TS_RFC850, timezone -02' => [ 'Tuesday, 31-Jul-12 17:01:08 -02', '1343761268.000000' ],
			'TS_RFC850, timezone -0200' => [ 'Tuesday, 31-Jul-12 17:01:08 -0200', '1343761268.000000' ],
			'TS_RFC850, timezone -02:00' => [ 'Tuesday, 31-Jul-12 17:01:08 -02:00', '1343761268.000000' ],
			'TS_RFC850, timezone EDT' => [ 'Tuesday, 31-Jul-12 15:01:08 EDT', '1343761268.000000' ],
			'TS_RFC850, timezone X' => [ 'Tuesday, 31-Jul-12 08:01:08 X', '1343761268.000000' ],
			'TS_RFC850, timezone CEST' => [ 'Tuesday, 31-Jul-12 21:01:08 CEST', '1343761268.000000' ],
			'asctime' => [ 'Tue Jul 31 19:01:08 2012', '1343761268.000000' ],
			'asctime, one-digit day' => [ 'Tue Jul  1 19:01:08 2012', '1341342068.000000' ],
			'asctime, one-digit day without space' => [ 'Tue Jul 1 19:01:08 2012', '1341342068.000000' ],
			'asctime, with newline' => [ "Tue Jul 31 19:01:08 2012\n", '1343761268.000000' ],

			// Testing timezone handling
			'TS_POSTGRES w/zone => TS_MW' => [ '2012-07-31 21:01:08+02', '20120731190108', TS_MW ],
			'TS_RFC2822 w/zone => TS_MW' => [ 'Tue, 31 Jul 2012 15:01:08 EDT', '20120731190108', TS_MW ],
			'TS_RFC850 w/zone => TS_MW' => [ 'Tuesday, 31-Jul-12 17:01:08 -02:00', '20120731190108', TS_MW ],
		];
	}

	/**
	 * @dataProvider provideParseOnly
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testValidParseOnly( $original, $expected, $format = TS_UNIX_MICRO ) {
		$timestamp = new ConvertibleTimestamp( $original );
		$this->assertEquals( $expected, $timestamp->getTimestamp( $format ) );
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
			'TS_UNIX_MICRO, excess precision' => [ '1343761268.123456789' ],
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
		$timestamp = new ConvertibleTimestamp( '2012-07-31 19:01:08' );
		$this->assertSame( '2012-07-31 19:01:08+0000', $timestamp->format( 'Y-m-d H:i:sO' ) );
		$timestamp->setTimezone( 'America/New_York' );
		$this->assertSame( '2012-07-31 15:01:08-0400', $timestamp->format( 'Y-m-d H:i:sO' ) );

		$timestamp = new ConvertibleTimestamp( 'Tue, 31 Jul 2012 15:01:08 EDT' );
		$this->assertSame( '2012-07-31 15:01:08-0400', $timestamp->format( 'Y-m-d H:i:sO' ) );
		$timestamp->setTimezone( 'UTC' );
		$this->assertSame( '2012-07-31 19:01:08+0000', $timestamp->format( 'Y-m-d H:i:sO' ) );
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
		$fakeTime = (int)ConvertibleTimestamp::convert( TS_UNIX, '20010101000000' );
		$fakeClock = $fakeTime;
		ConvertibleTimestamp::setFakeTime( function () use ( &$fakeClock ) {
			return $fakeClock++;
		} );
		$this->assertSame( $fakeTime, ConvertibleTimestamp::time() );
		$this->assertSame( '20010101000001', ConvertibleTimestamp::now() );
		$this->assertSame( '20010101000002', ConvertibleTimestamp::convert( TS_MW, false ) );
		$this->assertSame( '20010101000003', ConvertibleTimestamp::now() );
		$this->assertSame( $fakeTime + 4, ConvertibleTimestamp::time() );

		// fake time stays put
		$old = ConvertibleTimestamp::setFakeTime( '20200202112233' );
		$this->assertTrue( is_callable( $old ) );

		$fakeTime = (int)ConvertibleTimestamp::convert( TS_UNIX, '20200202112233' );
		$this->assertSame( $fakeTime, ConvertibleTimestamp::time() );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::now() );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::convert( TS_MW, false ) );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::now() );

		// no more fake time
		$old = ConvertibleTimestamp::setFakeTime( false );
		$this->assertInstanceOf( Closure::class, $old );
		$this->assertSame( '20200202112233', ConvertibleTimestamp::convert( TS_MW, $old() ) );

		$this->assertNotSame( '20200202112233', ConvertibleTimestamp::now() );
		$this->assertNotSame( $fakeTime, ConvertibleTimestamp::time() );
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
			[ TS_UNIX, '-62135596801', TS_MW, '00001231235959' ],
			[ TS_UNIX_MICRO, '-1.100000', TS_ORACLE, '31-12-1969 23:59:58.900000' ],
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
			// Odd cases formerly accepted
			[ '2019-05-22T12:00:00.....1257' ],
			[ '2019-05-22T12:00:001257' ],
			[ '2019-05-22T12:00:00.....' ],
			[ '20190522T120000.....1257' ],
			[ '20190522T1200001257' ],
			[ '20190522T120000.....' ],
			[ '2019-05-22 12:00:00....1257-04' ],
			[ '2019-05-22 12:00:001257-04' ],
			[ '2019-05-22 12:00:00...-04' ],
			[ '2019-05-22 12:00:00....1257 GMT' ],
			[ '2019-05-22 12:00:001257 GMT' ],
			[ '2019-05-22 12:00:00... GMT' ],
			[ 'Wed, 22 May 2019 12:00:00 A potato' ],
			[ 'Wed, 22 May 2019 12:00:00 + 2 days' ],
			[ 'Wed, 22 May 2019 12:00:00 Monday' ],
			[ 'Wednesday, 22-May-19 12:00:00 A potato' ],
			[ 'Wednesday, 22-May-19 12:00:00 + 2 days' ],
			[ 'Wednesday, 22-May-19 12:00:00 Monday' ],
			[ 'Wed May 22 12:00:00 2019 A potato' ],
			[ 'Wed May 22 12:00:00 2019 + 2 days' ],
			[ 'Wed May 22 12:00:00 2019 Monday' ],
			[ 'Tue Jul 31 19:01:08 2012 UTC' ],
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
