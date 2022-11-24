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
use DateInterval;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class ConvertibleTimestampTest extends \PHPUnit\Framework\TestCase {

	protected function tearDown(): void {
		parent::tearDown();
		ConvertibleTimestamp::setFakeTime( false );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::__construct
	 */
	public function testConstructWithoutTimestamp() {
		$timestamp = new ConvertibleTimestamp();
		$this->assertIsString( $timestamp->getTimestamp() );
		$this->assertNotEmpty( $timestamp->getTimestamp() );
		$this->assertNotFalse( strtotime( $timestamp->getTimestamp( TS_MW ) ) );
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
		// Value is equivalent to 20140731190108
		$timestamp = new ConvertibleTimestamp( '1406833268' );
		$this->assertSame( '1406833268', $timestamp->__toString() );

		$timestamp = new ConvertibleTimestamp( '20140731190108' );
		$this->assertSame( '1406833268', $timestamp->__toString() );
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
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::add
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::sub
	 */
	public function testAddSub() {
		$timestamp = new ConvertibleTimestamp( '2022-01-01 10:00:00' );
		$timestamp->add( new DateInterval( 'P1D' ) );
		$this->assertEquals( '2022-01-02 10:00:00', $timestamp->getTimestamp( TS_DB ) );
		$timestamp->add( 'P1D' );
		$this->assertEquals( '2022-01-03 10:00:00', $timestamp->getTimestamp( TS_DB ) );
		$timestamp->sub( new DateInterval( 'P1D' ) );
		$this->assertEquals( '2022-01-02 10:00:00', $timestamp->getTimestamp( TS_DB ) );
		$timestamp->sub( 'P1D' );
		$this->assertEquals( '2022-01-01 10:00:00', $timestamp->getTimestamp( TS_DB ) );
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
		// Parsing of the 2-digit year in RFC 850 format is tested more extensively below.
		// For this test, just make sure it doesn't break in 2062.
		ConvertibleTimestamp::setFakeTime( static function () {
			return 1570123766;
		} );

		$timestamp = new ConvertibleTimestamp( $original );
		$this->assertEquals( $expected, $timestamp->getTimestamp( $format ) );
	}

	public static function provide2DigitYearHandling() {
		return [
			'00 in 2019' => [ '2019', '00', '2000' ],
			'69 in 2019' => [ '2019', '69', '2069' ],
			'70 in 2019' => [ '2019', '70', '1970' ],
			'70 in 2020' => [ '2020', '70', '2070' ],
			'71 in 2020' => [ '2020', '71', '1971' ],

			'19 in 2069' => [ '2069', '19', '2119' ],
			'20 in 2069' => [ '2069', '20', '2020' ],
			'20 in 2070' => [ '2070', '20', '2120' ],
			'21 in 2070' => [ '2070', '21', '2021' ],

			'12 in 1820' => [ '1820', '12', '1812' ],
			'12 in 1890' => [ '1890', '12', '1912' ],
			'12 in 2320' => [ '2320', '12', '2312' ],
			'12 in 2390' => [ '2390', '12', '2412' ],
		];
	}

	/**
	 * @dataProvider provide2DigitYearHandling
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function test2DigitYearHandling( $thisYear, $inYear, $outYear ) {
		$tz = new \DateTimeZone( 'UTC' );

		// We test with an "now" at the beginning and end of the year
		$nowTimes = [ "$thisYear-01-01 00:00:00", "$thisYear-12-31 23:59:59" ];

		// Test a timestamp in the middle of the year, plus for sanity checking
		// a timestamp that's actually in the adjacent UTC-years.
		// We need to get the day-of-week right, or else PHP adjusts the date to make it match.
		$timestamps = [];
		$day = \DateTime::createFromFormat( 'Y-m-d', "$outYear-07-31", $tz )->format( 'l' );
		$timestamps[] = [ "$day, 31-Jul-$inYear 00:00:00 +00", $outYear ];
		$day = \DateTime::createFromFormat( 'Y-m-d', "$outYear-12-31", $tz )->format( 'l' );
		$timestamps[] = [ "$day, 31-Dec-$inYear 23:59:59 -01", $outYear + 1 ];
		$day = \DateTime::createFromFormat( 'Y-m-d', "$outYear-01-01", $tz )->format( 'l' );
		$timestamps[] = [ "$day, 01-Jan-$inYear 00:00:00 +01", $outYear - 1 ];

		foreach ( $nowTimes as $nowTime ) {
			$now = \DateTime::createFromFormat( 'Y-m-d H:i:s', $nowTime, $tz )->getTimestamp();
			$this->assertSame( $nowTime, gmdate( 'Y-m-d H:i:s', $now ), 'sanity check' );
			ConvertibleTimestamp::setFakeTime( static function () use ( $now ) {
				return $now;
			} );

			foreach ( $timestamps as [ $ts, $expectYear ] ) {
				$timestamp = new ConvertibleTimestamp( $ts );
				$timestamp->setTimezone( 'UTC' );
				$this->assertEquals(
					$expectYear,
					$timestamp->format( 'Y' ),
					"$ts at $nowTime UTC"
				);
			}
		}
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testValidParseZero() {
		$now = time();
		$timestamp = new ConvertibleTimestamp( 0 );
		$this->assertEqualsWithDelta(
			$now,
			$timestamp->getTimestamp( TS_UNIX ),
			10.0,
			'now'
		);
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::now
	 */
	public function testNow() {
		$this->assertEqualsWithDelta(
			time(),
			ConvertibleTimestamp::now( TS_UNIX ),
			10.0,
			'now'
		);
	}

	/**
	 * Parse invalid timestamps.
	 *
	 * @dataProvider provideInvalidTimestamps
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setTimestamp
	 */
	public function testInvalidParse( $input ) {
		$this->expectException( \Wikimedia\Timestamp\TimestampException::class );
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
	 */
	public function testOutOfRangeTimestamps( $format, $input ) {
		$timestamp = new ConvertibleTimestamp( $input );
		$this->expectException( \Wikimedia\Timestamp\TimestampException::class );
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
	 */
	public function testInvalidFormat( $format ) {
		$timestamp = new ConvertibleTimestamp( '1343761268' );
		$this->expectException( \Wikimedia\Timestamp\TimestampException::class );
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
	 */
	public function testSetTimezoneInvalid() {
		$timestamp = new ConvertibleTimestamp( 0 );
		$this->expectException( \Wikimedia\Timestamp\TimestampException::class );
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
		ConvertibleTimestamp::setFakeTime( static function () use ( &$fakeClock ) {
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

		$fakeTime = new ConvertibleTimestamp( '20200202030201' );
		ConvertibleTimestamp::setFakeTime( $fakeTime );
		$this->assertSame( (int)$fakeTime->getTimestamp(), ConvertibleTimestamp::time() );

		// test starting at date string
		$ts = '2020-01-01T03:04:05Z';
		$timestampUnix = (int)ConvertibleTimestamp::convert( TS_UNIX, $ts );
		ConvertibleTimestamp::setFakeTime( $ts );
		$this->assertSame( $timestampUnix, ConvertibleTimestamp::time() );

		// test starting with a ConvertibleTimestamp object
		$ts = new ConvertibleTimestamp( '2020-01-01T03:04:05Z' );
		ConvertibleTimestamp::setFakeTime( $ts );
		$this->assertSame( (int)$ts->getTimestamp(), ConvertibleTimestamp::time() );

		// no more fake time
		$old = ConvertibleTimestamp::setFakeTime( false );
		$this->assertInstanceOf( Closure::class, $old );
		$this->assertSame( $ts->getTimestamp( TS_MW ), ConvertibleTimestamp::convert( TS_MW, $old() ) );

		$this->assertNotSame( $ts->getTimestamp( TS_MW ), ConvertibleTimestamp::now() );
		$this->assertNotSame( $fakeTime, ConvertibleTimestamp::time() );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setFakeTime
	 */
	public function testFakeTimeWithStep() {
		$wallClockTime = time();

		// test starting at number, step 1
		$ts = 12345678;
		ConvertibleTimestamp::setFakeTime( $ts, 1 );
		$this->assertSame( $ts + 0, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 1, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 2, ConvertibleTimestamp::time() );

		// test starting at number, step 2
		$ts = 12345678;
		ConvertibleTimestamp::setFakeTime( $ts, 2 );
		$this->assertSame( $ts + 0, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 2, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 4, ConvertibleTimestamp::time() );

		// test starting at number, step 0.75
		$ts = 12345678;
		ConvertibleTimestamp::setFakeTime( $ts, 0.75 );
		$this->assertSame( $ts + 0, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 0, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 1, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 2, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 3, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 3, ConvertibleTimestamp::time() );
		$this->assertSame( $ts + 4, ConvertibleTimestamp::time() );

		// back to real time
		ConvertibleTimestamp::setFakeTime( false );
		$this->assertGreaterThanOrEqual( $wallClockTime, ConvertibleTimestamp::time() );
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::setFakeTime
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::microtime
	 */
	public function testFakeMicroTime() {
		$t = 1000000;
		ConvertibleTimestamp::setFakeTime( $t );
		$time = ConvertibleTimestamp::time();
		$this->assertEquals( $t, $time );

		$microtime = ConvertibleTimestamp::microtime();
		$this->assertGreaterThanOrEqual( $t, $microtime );
		$this->assertLessThanOrEqual( 1, $microtime - $t );

		$microtime2 = ConvertibleTimestamp::microtime();
		$this->assertGreaterThan( $microtime, $microtime2 );
		$this->assertLessThan( 1, $microtime2 - $microtime );

		$t2 = $t + 1;
		ConvertibleTimestamp::setFakeTime( $t2 );
		$time = ConvertibleTimestamp::time();
		$this->assertEquals( $t2, $time );

		$microtime = ConvertibleTimestamp::microtime();
		$this->assertGreaterThanOrEqual( $t2, $microtime );
		$this->assertGreaterThan( $microtime2, $microtime );
		$this->assertLessThanOrEqual( 1, $microtime - $t2 );
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
		// Various formats
		return [
			// -0001-12-31T23:59:59Z
			[ TS_MW, '-62167219201' ],
			// 10000-01-01T00:00:00Z
			[ TS_MW, '253402300800' ],
		];
	}

	/**
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::time
	 * @covers \Wikimedia\Timestamp\ConvertibleTimestamp::microtime
	 */
	public function testClockTime() {
		$time = ConvertibleTimestamp::time();
		$microtime = ConvertibleTimestamp::microtime();

		$this->assertGreaterThanOrEqual( 0, $microtime - $time );
		$this->assertLessThanOrEqual( 1, $microtime - $time );
	}

}
