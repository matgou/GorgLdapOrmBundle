<?php

namespace Gorg\Bundle\LdapOrmBundle\Ldap\Filter;

class LdapFilter
{
	private $filterData;
        private $operator;

	function __construct($filterArray = array(), $operator = "AND")
	{
		$this->filterData = $filterArray;
                if($operator == "AND") {
                    $this->operator = "&";
                } else {
                    $this->operator = "|";
                }
	}

	public function format($type)
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
