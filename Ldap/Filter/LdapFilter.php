<?php

namespace Gorg\Bundle\LdapOrmBundle\Ldap\Filter;

class LdapFilter
{
	private $filterData;

	function __construct($filterArray = array())
	{
		$this->filterData = $filterArray;
	}

	public function format($type)
	{
		$returnString = "";
                $sufix = "";
                if(count($this->filterData) > 1) 
                {
                    $returnString .= '(&';
                    $sufix .= ')';
                }
		foreach($this->filterData as $key => $value)
		{
			$returnString .= '('  . $key . '=' . $value . ')';
		}
                $returnString .= $sufix;
		return $returnString;
	}
}
