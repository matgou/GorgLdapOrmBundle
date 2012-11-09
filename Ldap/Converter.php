<?php

namespace Gorg\Bundle\LdapOrmBundle\Ldap;

/**
 * Converter for LDAP
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
class Converter
{

    /**
     * Convert an LDAP-Generalized-Time-entry into a DateTime-Object
     *
     * CAVEAT: The DateTime-Object returned will always be set to UTC-Timezone.
     *
     * @param string $date The generalized-Time
     * @param boolean $asUtc Return the DateTime with UTC timezone
     * @return DateTime
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

        // Raw-Data is present, so lets create a DateTime-Object from it.
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
        $date = new \DateTime($timestring);
        if ($asUtc) {
            $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $date;
    }


    /**
     * Converts a date-entity to an LDAP-compatible date-string
     *
     * The date-entity <var>$date</var> can be either a timestamp, a
     * DateTime Object, a string that is parseable by strtotime().
     *
     * @param integer|string|DateTime $date The date-entity
     * @param boolean $asUtc Whether to return the LDAP-compatible date-string as UTC or as local value
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public static function toLdapDateTime($date, $asUtc = true)
    {
        if (!($date instanceof \DateTime)) {
            if (is_int($date)) {
                $date = new DateTime('@' . $date);
                $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
            } elseif (is_string($date)) {
                $date = new DateTime($date);
            } else {
                throw new Exception('Parameter $date is not of the expected type');
            }
        }
        $timezone = $date->format('O');
        if (true === $asUtc) {
            $date->setTimezone(new \DateTimeZone('UTC'));
            $timezone = 'Z';
        }
        if ('+0000' === $timezone) {
            $timezone = 'Z';
        }

        return $date->format('YmdHis') . $timezone;
    }

}
