<?php
/***************************************************************************
 * Copyright (C) 1999-2013 Gadz.org                                        *
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

namespace Gorg\Bundle\LdapOrmBundle\Entity;

/**
 * Class used to Decorate DateTime objects (and permits them to be printed)
 *
 * @author Eric Bourderau <eric.bourderau@soce.fr>
 * @category API
 * @package  GramApiServerBundle
 * @license  GNU General Public License
 */
class DateTimeDecorator
{
    protected $_instance;
    
    private $format = 'YmdHisO'; // default for ldap

    public function __toString() {
        return $this->_instance->format($this->format);
    }
    
    public function setFormat($format) {
        $this->format = $format;
    }

    /**
     * Decorator of a DateTime object (adding __toString() and setFormat method)
     * @param type $datetime
     */
    public function __construct($datetime) {
        $this->_instance = new \DateTime($datetime);
    }

    public function __call($method, $args) {
        return call_user_func_array(array($this->_instance, $method), $args);
    }

    public function __get($key) {
        return $this->_instance->$key;
    }

    public function __set($key, $val) {
        return $this->_instance->$key = $val;
    }
}
