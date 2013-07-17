<?
	/**
     * This file is a part of EPP-DRS <http://epp-drs.com> distribution.
     * @category EPP-DRS
     * @package Common
     * @sdk
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */

	/**
     * Incapsulates HTML form. Used to construct type-safe HTML forms.  
     * @name DataForm
     * @package    Common
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     * @see http://webta.net/docs/wiki/epp-drs-api-docs
     */
	class DataForm
	{
		
		
		/**
		 * Form fields. Array of DataForm objects.
		 *
		 * @var array
		 */
		public $Fields;
		
		/**
		 * Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @var string
		 */
		protected $InlineHelp; 
		
		/**
		 * XML manifest
		 *
		 * @var SimpleXMLElement
		 */
		protected $XMLValidator;
		
		protected $Validators = array();
		
		private static $DefaultFieldset = "default";
		private $Fieldsets;
		
		function __clone()
		{
			$Fields = array();
			foreach ($this->Fields as $k => $Field)
				$Fields[$k] = clone $Field;
			$this->Fields = $Fields;
		}
		
		/**
		 * Returns Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @return unknown
		 */
		function GetInlineHelp()
		{
			return $this->InlineHelp;
		}
		
		/**
		 * Set Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @param string $inline_help
		 */
		function SetInlineHelp($inline_help)
		{
			$this->InlineHelp = $inline_help;
		}
		
		/**
		 * Append form field.
		 * @param FormField
		 */
		function AppendField(DataFormField $field, $fieldset=null)
		{
			if ($field instanceof DataFormField)
			{
				$this->Fields[$field->Name] = $field;
				$this->Fieldsets[null !== $fieldset ? $fieldset : self::$DefaultFieldset][$field->Name] = $field;
			}
			else
			{ 
				throw new Exception(_("Field must be an instance of DataFormField"));
			}
		}
		
		/**
		 * Returns array of form fields (DataFormField objects); 
		 * @return array
		 */
		function ListFields($fieldset=null)
		{
			return $this->Fieldsets[null !== $fieldset ? $fieldset : self::$DefaultFieldset];
		}
		
		function ListFieldsets()
		{
			$ret = array_keys($this->Fieldsets);
			return count($ret) == 1 && $ret[0] == self::$DefaultFieldset ? array() : $ret;
		}
		
		/**
		 * Clear fields
		 *
		 */
		function ClearFields()
		{
			$this->Fields = array();
			$this->Fieldsets = array();
		}
		
		/**
		 * Return field object by name
		 * @return DataFormField
		 */
		function GetFieldByName($name)
		{
			return (isset($this->Fields[$name])) ? $this->Fields[$name] : null;
		}
		
		public function AppendFromXML (SimpleXMLElement $root)
		{
			foreach ($root->field as $xmlfield)
			{
				$this->AppendFieldFromXML($xmlfield);
			}
		}
		
		/**
		 * Append form field from XML definition
		 *
		 * @param SimpleXMLElement $xmlfield xml definition of the field
		 * @param string $newname Name of the attached field. By default takes from xml 
		 */
		public function AppendFieldFromXML (SimpleXMLElement $xmlfield, $name=null)
		{
			$field = (array)$xmlfield; 
			$field = $field["@attributes"];
			
			$form_field = new DataFormField(
				$name ? $name : $field['name'],
				$field['type'],
				$field['description'],
				$field['required'],
				array(),
				$field["value"],
				$field["value"],
				$field["hint"]
			);
			if ($form_field->FieldType == FORM_FIELD_TYPE::SELECT)
			{
				$options = array();
				if ($xmlfield->values)
				{
					// TODO:
				}
				else if ($xmlfield->database)
				{
					// TODO:
				}
				$form_field->Options = $options;
			}
			
			$this->AppendField($form_field);
		}
		
		
		public function SaveToXML ($root=null)
		{
			// TODO:
			// save form structire to xml
		} 
		
		
		/**
		 * Returns array of error messages. 
		 *
		 * @param array $data
		 * @return array Empty when form passes validation
		 */
		private function ValidateOverXML ($data)
		{
			$err = array();
			foreach ($this->XMLValidator->field as $field)
			{
				settype($field, "array");			
				$field = $field["@attributes"];
				$field_name = $field["name"];
				
				if ($field_name)
				{
					$description = ucfirst($field['description']);
						
					if ($field['iseditable'] == 0)
					{
						if ($field['iseditable'] == 0 && isset($data[$field_name]) && isset($this->Fields[$field_name]->Value))
						{
							$err[$field_name] = sprintf(_("'%s' is uneditable"), $description);
							continue;
						}
					}
					
					
					if ($field['required'] && (!strlen($data[$field_name]) && ($field['iseditable'] || (!$field['iseditable'] && !array_key_exists($field_name, $this->Fields)))))
					{
						$err[$field_name] = sprintf(_("'%s' is required"), $description);
					}
					elseif (strlen($data[$field_name]))
					{
						if ($field["minlength"])
							if (strlen($data[$field_name]) < (int)$field["minlength"])
							{
								$err[$field_name] = sprintf(_("'%s' must contain at least %d chars"), $description, (int)$field["minlength"]);
							}
								
						if ((int)$field["maxlength"])
							if (strlen($data[$field_name]) > (int)$field["maxlength"])
							{
								$err[$field_name] = sprintf(_("'%s' cannot be longer than %d chars"), $description, (int)$field["maxlength"]);
							}
							
						if ($field["pattern"] && !preg_match("{$field['pattern']}msi", $data[$field_name]))
						{
							$err[$field_name] = sprintf(_("'%s' is not valid"), $description);
						}
					}
					
					if ($field['type'] == FORM_FIELD_TYPE::PHONE && $data[$field_name])
					{
						$Phone = Phone::GetInstance();
						if ($Phone->IsE164($data[$field_name]) || $Phone->IsPhone($data[$field_name]))
							continue;
						else
							$err[$field_name] = sprintf(_('%s must be in %s format'), $description, CONFIG::$PHONE_FORMAT); 
					}
				}
			}
			return $err;
		}
		
		function Validate ($data)
		{
			// Apply XML validator
			$err = $this->XMLValidator ? $this->ValidateOverXML($data) : array(); 
			
			// Apply programatical validators
			foreach ($this->Fields as $field)
				foreach ($this->Validators as $k => $validator)
					if ((is_numeric($k) || $k == $field->Name) && !key_exists($field->Name, $err))
						if ($msg = call_user_func($validator, $field->Name, $field->Value, $data))
							$err[$field->Name] = $msg;

				
			return $err;
		}
		
		/**
		 * Add form manifest for validation
		 *
		 * @param SimpleXMLElement $manifest
		 * @return DataForm
		 */
		function AddXMLValidator (SimpleXMLElement $manifest)
		{
			$this->XMLValidator = $manifest;
			return $this;
		}
		
		/**
		 * Add custom validator callback. It will be called for field, named $field with args:
		 * 	name	string 	field name
		 * 	value	mixed	field value
		 * 	data	array	all data passed for validation
		 *
		 * @param callable $callable
		 * @param string $field Field name, if no any -- callback will be used for each field 
		 * @return DataForm
		 */
		function AddValidator ($callable, $field = null)
		{
			if (is_callable($callable))
				$field ? $this->Validators[$field] = $callable : $this->Validators[] = $callable;
			return $this;
		}
		
		function Bind ($data)
		{
			foreach ($this->Fields as $name => $field)
			{
				$field->Value = $data[$name];
			}
		}		
	}

?>