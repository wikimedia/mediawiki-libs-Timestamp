<?php
/**
 * Timestamp format enumeration.
 *
 * Copyright (C) 2025 C. Scott Ananian <cananian@wikimedia.org>
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
 * @author C. Scott Ananian <cananian@wikimedia.org>
 */

namespace Wikimedia\Timestamp;

enum TimestampFormat: int {
	/**
	 * Unix time - the number of seconds since 1970-01-01 00:00:00 UTC
	 */
	case UNIX = 0;

	/**
	 * MediaWiki concatenated string timestamp (YYYYMMDDHHMMSS)
	 */
	case MW = 1;

	/**
	 * MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
	 */
	case DB = 2;

	/**
	 * RFC 2822 format, for E-mail and HTTP headers
	 */
	case RFC2822 = 3;

	/**
	 * ISO 8601 format with no timezone: 1986-02-09T20:00:00Z
	 */
	case ISO_8601 = 4;

	/**
	 * An Exif timestamp (YYYY:MM:DD HH:MM:SS)
	 *
	 * @see http://exif.org/Exif2-2.PDF The Exif 2.2 spec, see page 28 for the
	 *       DateTime tag and page 36 for the DateTimeOriginal and
	 *       DateTimeDigitized tags.
	 */
	case EXIF = 5;

	/**
	 * Oracle format time.
	 */
	case ORACLE = 6;

	/**
	 * Postgres format time.
	 */
	case POSTGRES = 7;

	/**
	 * ISO 8601 basic format with no timezone: 19860209T200000Z.
	 */
	case ISO_8601_BASIC = 9;

	/**
	 * UNIX time with microseconds
	 */
	case UNIX_MICRO = 10;
}
