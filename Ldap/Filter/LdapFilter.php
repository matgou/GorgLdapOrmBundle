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
 
namespace Gorg\Bundle\LdapOrmBundle\Ldap\Filter;

use Gorg\Bundle\LdapOrmBundle\Exception\Filter\InvalidLdapFilterException;

class LdapFilter
{
	private $filterData;
        private $operator;

	function __construct($filterArray = array(), $operator = "AND")
	{
		$this->filterData = $filterArray;
                if($operator == "AND") {
                    $this->operator = "&";
                } elseif($operator == "OR") {
                    $this->operator = "|";
                } else {
                    throw new InvalidLdapFilterException(sprintf('The second argument of LdapFilter must be "OR" or "AND" ("%s" given)', $operator));
                }
	}

	public function format($type = null)
	{
		$returnString = "";
                $sufix = "";
                if(count($this->filterData) > 1) 
                {
                    $returnString .= '(' . $this->operator;
                    $sufix .= ')';
                }
		foreach($this->filterData as $key => $value)
		{
			$returnString .= '('  . $key . '=' . $value . ')';
		}
                $returnString .= $sufix;
                if($returnString == null) {
                    $returnString="objectclass=*";
                }
		return $returnString;
	}
}
