<?php

/**
 * Phone filed object. Simplifies data convertion and vaidation.
 * @category EPP-DRS
 * @package Common
 * @sdk-doconly
 */
class Phone
{
	private 
		$Format,
		$ParsedFormat,
		$PhonePattern; 
		
	public function __construct()
	{
		$format = CONFIG::$PHONE_FORMAT;
		
		
		// Phone pattern
		$this->PhonePattern = 
			preg_replace('/\[(\d+)\]/', '(\d{$1})', 
			preg_replace('/\[(\d+)-(\d+)\]/', '(\d{$1,$2})',
			preg_replace('/\[cc\]/', '([\d\-]{1,5})', 
			preg_replace('/([\^\$\|\(\)\?\*\+\{\}\.])/', '\\\\$1', $format))));
			
		
		// Селект с call кодами стран
		$cc_pattern = '\[cc\]';
		
		// Поле ввода номера с жестким ограничением количества цифр
		$number_pattern = '\[\d+\]';
		$number_m_pattern = '\[(\d+)\]';
		
		// Поле воода номера с диапозоном
		$number2_pattern = '\[\d+-\d+\]';
		$number2_m_pattern = '\[(\d+)-(\d+)\]';


		
		// Разбиваем паттерн телефона на контролы и текстовые разделители между ними.			
		$pattern = "/($cc_pattern|$number_pattern|$number2_pattern)/";
		preg_match_all($pattern, $format, $numbers);
		$delimiters = preg_split($pattern, $format);
		$numbers = $numbers[0];
			
		
		$parsed_format = array(
			'format' => $format,
			'items' => array()
		);
		$parsed_format_items = array();
		
			
		for($i=0, $delim_cnt=sizeof($delimiters), $ctrl_cnt=sizeof($numbers); //  
			$i<$delim_cnt;
			$i++)
		{
			// Для текстового разделителя используем формат аналогичный ContactFormSchema
			$parsed_format_items[] = array(
				'type' => 'delimiter',
				'value' => $delimiters[$i]
			);
			
			// Разделителей на 1 больше чем чисел
			if ($i<$ctrl_cnt)
			{
				$n = $numbers[$i];
				
				if (preg_match("/$cc_pattern/", $n))
				{
					// Call code
					$parsed_format_items[] = array(
						'type' => 'cc',
					);
				}
				else if (preg_match("/$number_m_pattern/", $n, $matches) || preg_match("/$number2_m_pattern/", $n, $matches))
				{
					// Поле ввода номера 
					
					$minlength = $matches[1];
					$maxlength = isset($matches[2]) ? $matches[2] : $minlength; // Количество допустимых цифр указано диапозоном 
					
					$parsed_format_items[] = array(
						'type' => 'number',
						'minlength' => $minlength,
						'maxlength' => $maxlength
					);
				}
			}
		}
		
		$parsed_format['items'] = $parsed_format_items;
		
		$this->ParsedFormat = $parsed_format;
		$this->Format = $format;
	}
	
	private static $Instance;	
	
	/**
	 * @return Phone
	 */
	public static function GetInstance ()
	{
		if (self::$Instance === null)
			self::$Instance = new Phone();
		return self::$Instance;
	}

	public function GetParsedFormat ()
	{
		return $this->ParsedFormat;
	}
	
	public function E164ToPhone ($e164_phone)
	{
		$cc_pattern = '/\+([\d\-]+)\./'; 
		
		preg_match($cc_pattern, $e164_phone, $matches);
		$cc = $matches[1];		
		$e164_phone = preg_replace($cc_pattern, '', $e164_phone);

		$ret = '';
		foreach ($this->ParsedFormat['items'] as $i => $item)
		{
			// insert call code
			if ($item['type'] == 'cc')
			{
				$ret .= $cc;
			}
			// copy numbers
			else if ($item['type'] == 'number')
			{
				$len = $item['minlength'] + floor(($item['maxlength'] - $item['minlength']) / 2);
				$ret .= substr($e164_phone, 0, $len);
				$e164_phone = substr($e164_phone, $len);
			}
			// last delimiter and unused numbers in input phone
			else if (
				$item['type'] == 'delimiter' && 
				$i == count($this->ParsedFormat['items']) - 1 &&
				strlen($e164_phone)) 
			{
				$ret .= $e164_phone . $item['value'];
			}
			// ordinary delimiter
			else if ($item['type'] == 'delimiter')
			{
				$ret .= $item['value'];
			}
		}
		
		return $ret;
	}
	
	public function PhoneToE164 ($string)
	{
		$match = preg_split('/[^0-9]+/', $string, -1, PREG_SPLIT_NO_EMPTY);
		$cc_len = $match ? strlen($match[0]) : 3; 
		
		$ns = preg_replace("/[^0-9]+/", "", $string);
    	$chunks = str_split($ns, $cc_len);
    	return trim("+".array_shift($chunks).".".implode("", $chunks));
	}
	
	public static function IsValidFormat ($format)
	{
		$retval = preg_match("/([A-Za-z0-9\+\.\-,_]*\[(cc|\d+|\d+-\d+)\][A-Za-z0-9\+\.\-,_]+)+(\[(cc|\d+|\d+-\d+)\])?[A-Za-z0-9\+\.\-,_]*/si", $format, $m);
		
		//var_dump($retval);
		//var_dump($m);
		
		return $retval;
	}
	
	public function IsE164 ($string)
	{
		return preg_match('/^\+[0-9]{1,3}\.[0-9]{1,15}$/', $string);
	}

	public function IsPhone ($string)
	{
		return preg_match('/' . $this->PhonePattern . '/', $string);
	}
	
	public function ParsePhone ($phone)
	{
		$parsed = $this->ParsedFormat;
		if (!$this->IsPhone($phone)) return false;
		
		foreach ($parsed['items'] as $i => $item)
		{
			$re = $skiplen = null;
			
			if ($item['type'] == 'cc')
			{
				$re = '/^(\d+)/';
			}
			else if ($item['type'] == 'number')
			{
				$re = '/^(\d{'.$item['minlength'].','.$item['maxlength'].'})/';
			}
			else if ($item['type'] == 'delimiter' && strlen($item['value']))
			{
				$skiplen = strlen($item['value']);
			}
			else
				continue;
			
			if ($re)
			{
				preg_match($re, $phone, $matches);
				$v = $matches[1];
				$phone = substr($phone, strlen($v));
			}
			else if ($skiplen)
			{
				$v = substr($phone, 0, $skiplen);
				$phone = substr($phone, $skiplen);
			}
			else
				continue;
				
			$parsed['items'][$i]['value'] = $v;
		}
		
		return $parsed;
	}
	
	public function GetWidget ($attrs=array())
	{
		if (!$this->Widget)
		{
			$widget = $this->GetParsedFormat();
			$widget['type'] = 'phone';
			foreach ($widget['items'] as $i => &$item)
			{
				if ($item['type'] == 'cc')
				{
					$callcode_data = Core::GetDBInstance()->GetAll("
						SELECT * FROM callcodes
					");
					$callcode_list = array('' => '');
					foreach ($callcode_data as $row)
						$callcode_list[$row['code']] = $row['code'];
					
					$item = array(
						'type' => 'select',
						'values' => $callcode_list
					);
				}
				else if ($item['type'] == 'delimiter')
				{
					$item = array(
						'type' => 'label',
						'value' => $item['value']
					);
				}
				else if ($item['type'] == 'number')
				{
					$item = array(
						'type' => 'text',
						'minlength' => $item['minlength'],
						'maxlength' => $item['maxlength']
					);
				}
			}
			
			$this->Widget = $widget;
		}
		
		return array_merge($this->Widget, $attrs);
	} 
	
}

?>