<?php
class XmlDataBinding
{
	function Marshall ($param) 
	{
		if (is_object($param))
		{
			// ComplexType
			if (method_exists($param, "ToXml"))
			{
				return $param->ToXml()->asXML();
			}
		}
		
		// SimpleType
		return (string)$param;		
	}
	
	function Unmarshall ($source) 
	{
		if ($source instanceof SimpleXMLElement)
			$element = $source;
		else
			$element = new SimpleXMLElement($source);
		
		$children = $element->children();
		if (count($children))
		{
			// ComplexType
			$class_name = $children[0]->getName();
			if ($class_name)
			{
				$object = new $class_name;
				if (method_exists($object, "FromXml"))
				{
					$object->FromXml($children[0]);
					return $object;
				}
			}
		}
			
		// SimpleType
		return (string)$element;		
	}
}
?>