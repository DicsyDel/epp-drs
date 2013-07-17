<?php
if(!defined("METAL_LIBRARY_XML_RSS_WRITER_CLASS"))
{
	define("METAL_LIBRARY_XML_RSS_WRITER_CLASS",1);

/*
 *
 * Copyright � (C) Manuel Lemos 2002
 *
 * @(#) $Id: rsswriterclass.class,v 1.7 2002/10/17 05:49:40 mlemos Exp $
 *
 */

class rss_writer_class extends xml_writer_class
{
	/*
	 * Protected variables
	 *
	 */
	var $root="";
	var $channel="";
	var $image="";
	var $textinput="";
	var $items=0;
	var $itemsequence="";
	
	/*
	 * Public variables
	 *
	 */
	var $specification="1.0";
	var $about="";
	var $rssnamespaces=array();
	var $allownoitems=0;
	var $generatedcomment="";
	
	
	/*
	 * Protected functions
	 *
	 */
	Function addrssproperties(&$properties,$parent,&$required,&$optional,$scope)
	{
		$noattributes=array();
		$required_properties=0;
		Reset($properties);
		$end=(GetType($property=Key($properties))!="string");
		for(;!$end;)
		{
			if(IsSet($required[$property]))
			{
				if($required[$property])
				{
					$this->error=("required ".$scope." property \"".$property."\" is already set");
					return 0;
				}
				$required[$property]=1;
				$required_properties++;
			}
			else
			{
				if(IsSet($optional[$property]))
				{
					if($optional[$property])
					{
						$this->error=("optional ".$scope." property \"".$property."\" is already set");
						return 0;
					}
					$optional[$property]=1;
				}
				else
				{
					if(GetType($colon=strpos($property,":",0))=="integer")
					{
						$namespace=substr($property,0,$colon);
						if(!(!strcmp($namespace,"rdf") || IsSet($this->rssnamespaces[$namespace])))
							$this->error=("the name space of property \"".$property."\" was not declared");
					}
					else
						$this->error=("\"".$property."\" is not a supported ".$scope." property");
				}
			}
			if(!($this->adddatatag($property,$noattributes,$properties[$property],$parent,$path)))
				return 0;
			Next($properties);
			$end=(GetType($property=Key($properties))!="string");
		}
		if($required_properties<count($required))
		{
			Reset($required);
			$end=(GetType($property=Key($required))!="string");
			for(;!$end;)
			{
				if(!($required[$property]))
				{
					$this->error=("it was not specified the required ".$scope." property \"".$property."\"");
					return 0;
				}
				Next($required);
				$end=(GetType($property=Key($required))!="string");
			}
		}
		return 1;
	}
	
	/*
	 * Public functions
	 *
	 */
	Function addchannel(&$properties)
	{
		if(strcmp($this->error,""))
			return 0;
		if(strcmp($this->channel,""))
		{
			$this->error="a channel was already added";
			return 0;
		}
		$channel_attributes=array();
		switch($this->specification)
		{
			case "0.9":
				$root="rdf:RDF";
				$attributes=array("xmlns:rdf"=>"http://www.w3.org/1999/02/22-rdf-syntax-ns#","xmlns"=>"http://my.netscape.com/rdf/simple/0.9/");
				$required=array("description"=>0,"link"=>0,"title"=>0);
				$optional=array();
				break;
			case "0.91":
				$root="rss";
				$attributes=array("version"=>$this->specification);
				$required=array("description"=>0,"language"=>0,"link"=>0,"title"=>0);
				$optional=array("copyright"=>0,"docs"=>0,"lastBuildDate"=>0,"managingEditor"=>0,"pubDate"=>0,"rating"=>0,"webMaster"=>0);
				break;
			case "1.0":
				//if(!strcmp($this->about,""))
			//	{
					//$this->error="it was not specified the about URL attribute";
					//return 0;
				//}
				$root="rdf:RDF";
				$attributes=array("xmlns:rdf"=>"http://www.w3.org/1999/02/22-rdf-syntax-ns#","xmlns"=>"http://purl.org/rss/1.0/");
				Reset($this->rssnamespaces);
				$end=(GetType($namespace=Key($this->rssnamespaces))!="string");
				for(;!$end;)
				{
					if(!strcmp($namespace,"rdf"))
					{
						$this->error="the rdf namespace is being redeclared";
						return 0;
					}
					$attributes[("xmlns:".$namespace)]=$this->rssnamespaces[$namespace];
					Next($this->rssnamespaces);
					$end=(GetType($namespace=Key($this->rssnamespaces))!="string");
				}
				$channel_attributes=array("rdf:about"=>$this->about);
				$required=array("description"=>0,"link"=>0,"title"=>0);
				$optional=array();
				break;
			default:
				$this->error="it was not specified a supported RSS specification version";
				return 0;
		}
		$this->addtag($root,$attributes,"",$path,1);
		$this->root=$path;
		if(!($this->addtag("channel",$channel_attributes,$this->root,$path,1)))
			return 0;
		if(!($this->addrssproperties($properties,$path,$required,$optional,"channel")))
			return 0;
		$this->channel=$path;
		return 1;
	}
	
	Function additem(&$properties)
	{
		if(strcmp($this->error,""))
			return 0;
		if(!strcmp($this->channel,""))
		{
			$this->error="the channel was not yet added";
			return 0;
		}
		if(strcmp($this->textinput,""))
		{
			$this->error="items can not be added to the channel after defining the textinput";
			return 0;
		}
		$attributes=array();
		switch($this->specification)
		{
			case "0.9":
				$parent=$this->root;
				break;
			case "0.91":
				$parent=$this->channel;
				break;
			case "1.0":
				if(IsSet($properties["link"]))
					$attributes["rdf:about"]=$properties["link"];
				$parent=$this->root;
				break;
			default:
				$this->error="it was not specified a supported RSS specification version";
				return 0;
		}
		if(!($this->addtag("item",$attributes,$parent,$path,1)))
			return 0;
		$required=array("link"=>0,"title"=>0);
		$optional=array("description"=>0);
		if(!($this->addrssproperties($properties,$path,$required,$optional,"item")))
			return 0;
		if(!strcmp($this->specification,"1.0"))
		{
			if(!strcmp($this->itemsequence,""))
			{
				$attributes=array();
				if(!($this->addtag("items",$attributes,$this->channel,$path,1) && $this->addtag("rdf:Seq",$attributes,$path,$path,1)))
					return 0;
				$this->itemsequence=$path;
			}
			$attributes=array("rdf:resource"=>$properties["link"]);
			if(!($this->addtag("rdf:li",$attributes,$this->itemsequence,$path,0)))
				return 0;
		}
		$this->items++;
		return 1;
	}
	
	Function addimage(&$properties)
	{
		if(strcmp($this->error,""))
			return 0;
		if(!strcmp($this->channel,""))
		{
			$this->error="the channel was not yet added";
			return 0;
		}
		if(strcmp($this->image,""))
		{
			$this->error="the channel image was already associated";
			return 0;
		}
		if($this->items!=0)
		{
			$this->error="the image can only be defined before adding the channel items";
			return 0;
		}
		$attributes=array();
		switch($this->specification)
		{
			case "0.9":
				$parent=$this->root;
				break;
			case "0.91":
				$parent=$this->channel;
				break;
			case "1.0":
				if(IsSet($properties["url"]))
					$attributes["rdf:about"]=$properties["url"];
				$parent=$this->root;
				break;
			default:
				$this->error="it was not specified a supported RSS specification version";
				return 0;
		}
		if(!($this->addtag("image",$attributes,$parent,$path,1)))
			return 0;
		$this->image=$path;
		$required=array("link"=>0,"title"=>0,"url"=>0);
		$optional=array("description"=>0,"width"=>0,"height"=>0);
		if(!($this->addrssproperties($properties,$this->image,$required,$optional,"image")))
			return 0;
		if(!strcmp($this->specification,"1.0"))
		{
			$attributes=array("rdf:resource"=>$properties["url"]);
			return $this->addtag("image",$attributes,$this->channel,$path,0);
		}
		return 1;
	}
	
	Function addtextinput(&$properties)
	{
		if(strcmp($this->error,""))
			return 0;
		if(!strcmp($this->channel,""))
		{
			$this->error="the channel was not yet added";
			return 0;
		}
		if(strcmp($this->textinput,""))
		{
			$this->error="the channel text input was already associated";
			return 0;
		}
		if($this->items==0 && !$this->allownoitems)
		{
			$this->error="it were not specified any items before defining the channel text input";
			return 0;
		}
		$attributes=array();
		switch($this->specification)
		{
			case "0.9":
				$parent=$this->root;
				break;
			case "0.91":
				$parent=$this->channel;
				break;
			case "1.0":
				if(IsSet($properties["link"]))
					$attributes["rdf:about"]=$properties["link"];
				$parent=$this->root;
				break;
			default:
				$this->error="it was not specified a supported RSS specification version";
				return 0;
		}
		if(!($this->addtag("textinput",$attributes,$parent,$path,1)))
			return 0;
		$this->textinput=$path;
		$required=array("description"=>0,"link"=>0,"name"=>0,"title"=>0);
		$optional=array();
		if(!($this->addrssproperties($properties,$this->textinput,$required,$optional,"textinput")))
			return 0;
		if(!strcmp($this->specification,"1.0"))
		{
			$attributes=array("rdf:resource"=>$properties["link"]);
			return $this->addtag("textinput",$attributes,$this->channel,$path,0);
		}
		return 1;
	}
	
	Function writerss(&$output)
	{
		if(strcmp($this->error,""))
			return 0;
		if(!strcmp($this->channel,""))
		{
			$this->error="it was not defined the RSS channel";
			return 0;
		}
		if($this->items==0 && !$this->allownoitems)
		{
			$this->error="it were not defined any RSS channel items";
			return 0;
		}
		switch($this->specification)
		{
			case "0.9":
				$this->dtdtype="PUBLIC";
				$this->dtddefinition="-//Netscape Communications//DTD RSS 0.9//EN";
				$this->dtdurl="http://my.netscape.com/publish/formats/rss-0.9.dtd";
				break;
			case "0.91":
				$this->dtdtype="PUBLIC";
				$this->dtddefinition="-//Netscape Communications//DTD RSS 0.91//EN";
				$this->dtdurl="http://my.netscape.com/publish/formats/rss-0.91.dtd";
				break;
			case "1.0":
				$this->dtdtype="";
				break;
			default:
				$this->error="it was not specified a supported RSS specification version";
				return 0;
		}
		return $this->write($output);
	}
};

}
?>