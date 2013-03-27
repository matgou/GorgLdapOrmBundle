<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/
 
namespace Gorg\Bundle\LdapOrmBundle\Ldap;

use Gorg\Bundle\LdapOrmBundle\Entity\DateTimeDecorator;

/**
 * Converter for LDAP
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 *         DateTimeDecorator Modifications by Eric Bourderau <eric.bourderau@soce.fr>
 */
class Converter
{

    /**
     * Convert an LDAP-Generalized-Time-entry into a DateTimeDecorator-Object
     *
     * CAVEAT: The DateTimeDecorator-Object returned will always be set to UTC-Timezone.
     *
     * @param string $date The generalized-Time
     * @param boolean $asUtc Return the DateTimeDecorator with UTC timezone
     * @return DateTimeDecorator
     * @throws Exception\InvalidArgumentException if a non-parseable-format is given
     */
    public static function fromLdapDateTime($date, $asUtc = true)
    {
        $datepart = array();
        if (!preg_match('/^(\d{4})/', $date, $datepart)) {
            throw new Exception\InvalidArgumentException('Invalid date format found');
        }

        if ($datepart[1] < 4) {
            throw new Exception\InvalidArgumentException('Invalid date format found (too short)');
        }

        $time = array(
            // The year is mandatory!
            'year' => $datepart[1],
            'month' => 1,
            'day' => 1,
            'hour' => 0,
            'minute' => 0,
            'second' => 0,
            'offdir' => '+',
            'offsethours' => 0,
            'offsetminutes' => 0
        );

        $length = strlen($date);

        // Check for month.
        if ($length >= 6) {
            $month = substr($date, 4, 2);
            if ($month < 1 || $month > 12) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid month)');
            }
            $time['month'] = $month;
        }

        // Check for day
        if ($length >= 8) {
            $day = substr($date, 6, 2);
            if ($day < 1 || $day > 31) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid day)');
            }
            $time['day'] = $day;
        }

        // Check for Hour
        if ($length >= 10) {
            $hour = substr($date, 8, 2);
            if ($hour < 0 || $hour > 23) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid hour)');
            }
            $time['hour'] = $hour;
        }

        // Check for minute
        if ($length >= 12) {
            $minute = substr($date, 10, 2);
            if ($minute < 0 || $minute > 59) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid minute)');
            }
            $time['minute'] = $minute;
        }

        // Check for seconds
        if ($length >= 14) {
            $second = substr($date, 12, 2);
            if ($second < 0 || $second > 59) {
                throw new Exception\InvalidArgumentException('Invalid date format found (invalid second)');
            }
            $time['second'] = $second;
        }

        // Set Offset
        $offsetRegEx = '/([Z\-\+])(\d{2}\'?){0,1}(\d{2}\'?){0,1}$/';
        $off = array();
        if (preg_match($offsetRegEx, $date, $off)) {
            $offset = $off[1];
            if ($offset == '+' || $offset == '-') {
                $time['offdir'] = $offset;
                // we have an offset, so lets calculate it.
                if (isset($off[2])) {
                    $offsetHours = substr($off[2], 0, 2);
                    if ($offsetHours < 0 || $offsetHours > 12) {
                        throw new Exception\InvalidArgumentException('Invalid date format found (invalid offset hour)');
                    }
                    $time['offsethours'] = $offsetHours;
                }
                if (isset($off[3])) {
                    $offsetMinutes = substr($off[3], 0, 2);
                    if ($offsetMinutes < 0 || $offsetMinutes > 59) {
                        throw new Exception\InvalidArgumentException('Invalid date format found (invalid offset minute)');
                    }
                    $time['offsetminutes'] = $offsetMinutes;
                }
            }
        }

        // Raw-Data is present, so lets create a DateTimeDecorator-Object from it.
        $offset = $time['offdir']
                      . str_pad($time['offsethours'], 2, '0', STR_PAD_LEFT)
                      . str_pad($time['offsetminutes'], 2, '0', STR_PAD_LEFT);
        $timestring = $time['year'] . '-'
                      . str_pad($time['month'], 2, '0', STR_PAD_LEFT) . '-'
                      . str_pad($time['day'], 2, '0', STR_PAD_LEFT) . ' '
                      . str_pad($time['hour'], 2, '0', STR_PAD_LEFT) . ':'
                      . str_pad($time['minute'], 2, '0', STR_PAD_LEFT) . ':'
                      . str_pad($time['second'], 2, '0', STR_PAD_LEFT)
                      . $time['offdir']
                      . str_pad($time['offsethours'], 2, '0', STR_PAD_LEFT)
                      . str_pad($time['offsetminutes'], 2, '0', STR_PAD_LEFT);
        $date = new DateTimeDecorator($timestring);
        if ($asUtc) {
            $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $date;
    }


    /**
     * Converts a date-entity to an LDAP-compatible date-string
     *
     * The date-entity <var>$date</var> can be either a timestamp, a
     * DateTimeDecorator Object, a string that is parseable by strtotime().
     *
     * @param integer|string|DateTime|DateTimeDecorator $date The date-entity
     * @param boolean $asUtc Whether to return the LDAP-compatible date-string as UTC or as local value
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public static function toLdapDateTime($date, $asUtc = true)
    {
        if (!($date instanceof \DateTime) && !($date instanceof DateTimeDecorator)) {
            if (is_int($date)) {
                $date = new DateTimeDecorator('@' . $date);
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            } elseif (is_string($date)) {
                $date = new DateTimeDecorator($date);
            } else {
                throw new Exception('Parameter $date is not of the expected type');
            }
        }
        if (true === $asUtc) {
            $date->setTimezone(new \DateTimeZone('UTC'));
        }

        return $date; // DateTimeDecorator has __toString magic method
    }

}
