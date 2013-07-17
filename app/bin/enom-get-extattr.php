<?php

	require_once ('../src/prepend.inc.php');
	
	$RegistryFactory = RegistryModuleFactory::GetInstance();
	$Registry = $RegistryFactory->GetRegistryByName('Enom');
	$Module = $Registry->GetModule();
	
	$tld_list = array(
//		'asia', 'biz','com','info','mobi','name','net','org',
//		'AC', 'AT', 'BE', 'BZ', 'CA', 'CC', 'CN', 'DE', 'EU', 'GS', 'IN', 'IO', 'IT', 'JP', 'LA', 'MS', 'NL', 'NU', 'NZ', 'SH', 'TC', 'TM', 'TV', 'TW', 'US', 'UK', 'VG', 'WS',
	
		'BR.COM', 'CN.COM', 'DE.COM', 'EU.COM', 'HU.COM', 'NO.COM', 'QC.COM', 'RU.COM', 'SA.COM', 'SE.COM', 'UK.COM', 'US.COM', 'UY.COM', 'ZA.COM', 'COM.MX', 'UK.NET', 'SE.NET', 'KIDS.US'
	); 
	
	foreach ($tld_list as $tld)
	{
		$params = array(
			'TLD' => $tld
		);
		$Resp = $Module->Request('GetExtAttributes', $params);
		
		print "$tld: \n";
		print count($Resp->Data->Attributes->Attribute) . "\n\n";
		if ($Resp->Data->Attributes)
		{
			$extra = new SimpleXMLElement('<extra_fields />');
			foreach ($Resp->Data->Attributes->Attribute as $Attribute)
			{
				// Determ field type
				$type = 'text';
				if ($Attribute->Options->Option)
				{
					$type = count($Attribute->Options->Option) > 1 ? 'select' : 'checkbox';
				}
				
				$field = $extra->addChild('field');
				$field->addAttribute('type', $type);
				$field->addAttribute('name', $Attribute->Name);				
				$field->addAttribute('description', $Attribute->Description);
				$field->addAttribute('required', $Attribute->Required);
				
				if ($type == 'checkbox')
				{
					$field->addAttribute('value', $Attribute->Options->Option->Value);
				}
				else if ($type == 'select')
				{
					$values = $field->addChild('values');
					foreach ($Attribute->Options->Option as $Option)
					{
						$value = $values->addChild('value');
						$value->addAttribute('name', $Option->Title);
						$value->addAttribute('value', $Option->Value);
					}
				}
			}
			
			file_put_contents("{$cachepath}/enom_{$tld}.xml", $extra->asXML());
		}
		print "\n";
		flush();
		
		//return;
	}
	
?>