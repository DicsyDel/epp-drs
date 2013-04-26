<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    Data
     * @subpackage RRD
     * @copyright  Copyright (c) 2003-2009 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
    /**
     * @name RRDGraph
     * @category   LibWebta
     * @package    Data
     * @subpackage RRD
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RRDGraph extends Core
	{
	    /**
	     * RRD Graph width
	     *
	     * @var integer
	     */
		public $Width;
		
		/**
		 * RRD Graph height
		 *
		 * @var integer
		 */
		public $Height;
		
		/**
		 * RRD Graph arguments
		 *
		 * @var array
		 */
		public $Args;
		
		/**
		 * RRD Graph title
		 *
		 * @var string
		 */
		public $Title;
		
		/**
		 * X grid style
		 *
		 * @var string
		 */
		public $XGridStyle;
		
		/**
		 * Y Grid style 
		 *
		 * @var string
		 */
		public $YGridStyle;
		
		/**
		 * RRD Graph constructor
		 *
		 * @param integer $width
		 * @param integer $height
		 */
		function __construct($width, $height)
		{
			$this->Width = $width;
			$this->Height = $height;
			
			$this->Title = false;
			$this->XGridStyle = false;
			$this->YGridStyle = false;
			
			
			$this->Args = array();
			
			$this->Fonts = array();
		}
		
		/**
		 * Add font
		 *
		 * @param string $tag RRD tag for this font
		 * @param integer $size Font size
		 * @param string $ttf_path Path to TTF file
		 */
		public function AddFont($tag, $size, $ttf_path = "")
		{
			$this->Fonts[] = "{$tag}:{$size}:{$ttf_path}";
		}
		
		/**
		 * plot graphic
		 *
		 * @param string $filename
		 * @param string $start
		 * @param string $end
		 * 
		 * @see http://oss.oetiker.ch/rrdtool/doc/rrdfetch.en.html
		 */
		public function Plot($filename, $start = false, $end = false, $args = array())
		{
			// add end timestamp
			if ($end)
			{
				array_push($args, "--end");
				array_push($args, $end);
			}
			
			// Add start timestamp
			if ($start)
			{
				array_push($args, "--start");
				array_push($args, $start);
			}
			
			// Set X-grid style
			if ($this->XGridStyle)
			{
				array_push($args, "--x-grid");
				array_push($args, $this->XGridStyle);
			}
			
			// Set X-grid style
			if ($this->YGridStyle)
			{
				array_push($args, "--y-grid");
				array_push($args, $this->YGridStyle);
			}
			
			// Set graphic style
			if ($this->Title)
			{
				array_push($args, "--title");
				array_push($args, $this->Title);
			}
			
			array_push($args, "--width");
			array_push($args, $this->Width);
			
			array_push($args, "--height");
			array_push($args, $this->Height);
			
			array_push($args, "--font-render-mode");
			array_push($args, "normal");
			
			foreach($this->Fonts as $font)
			{
				if ($font != "")
				{
					array_push($args, "--font");
					array_push($args, $font);
				}
			}
			
			$args = array_merge($args,$this->Args);

					
			$ret = @rrd_graph($filename, $args, count($args));
			
			if(!is_array($ret))
			{
				Core::RaiseError(_("Cannot plot graph: ").@rrd_error());
			}
			
			return true;
		}
		
		/**
		 * Set X-Grid Style
		 *
		 * @param string $GTM
		 * @param string $GST
		 * @param string $MTM
		 * @param string $MST
		 * @param string $LTM
		 * @param string $LST
		 * @param string $LPR
		 * @param string $LFM
		 * 
		 * The x-axis label is quite complex to configure. 
		 * If you don't have very special needs it is probably best to rely on the autoconfiguration to get this right. 
		 * You can specify the string none to suppress the grid and labels altogether.
		 * The grid is defined by specifying a certain amount of time in the ?TM positions. 
		 * You can choose from SECOND, MINUTE, HOUR, DAY, WEEK, MONTH or YEAR. 
		 * Then you define how many of these should pass between each line or label. 
		 * This pair (?TM:?ST) needs to be specified for the base grid (G??), the major grid (M??) and the labels (L??). 
		 * For the labels you also must define a precision in LPR and a strftime format string in LFM. 
		 * LPR defines where each label will be placed. 
		 * If it is zero, the label will be placed right under the corresponding line (useful for hours, dates etcetera). 
		 * If you specify a number of seconds here the label is centered on this interval (useful for Monday, January etcetera).
		 * 
		 * @see http://oss.oetiker.ch/rrdtool/doc/rrdgraph.en.html
		 */
		public function SetXGridStyle($GTM,$GST,$MTM,$MST,$LTM,$LST,$LPR,$LFM)
		{
			$this->XGridStyle = "{$GTM}:{$GST}:{$MTM}:{$MST}:{$LTM}:{$LST}:{$LPR}:{$LFM}";
		}
		
		/**
		 * Set Y-Grid style
		 *
		 * @param string $step
		 * @param string $label
		 * @param string $factor
		 * 
		 * Y-axis grid lines appear at each grid step interval. 
		 * Labels are placed every label factor lines. 
		 * You can specify -y none to suppress the grid and labels altogether. 
		 * The default for this option is to automatically select sensible values.
		 * 
		 */
		public function SetYGridStyle($step, $label, $factor)
		{
			$this->YGridStyle = "{$step}:{$label} {$factor}";
		}
		
		/**
		 * Magic function __call
		 *
		 * @param string $method
		 * @param array $args
		 */
		function __call($method, $args)
		{
			$method = substr($method, 3);
			
			switch($method)
			{
				
			    /**
			     * CDEF:vname=RPN expression
			     * $args[0] = vname
			     * $args[1] = RPN
			     */
			    case "CDEF":
			        
			         $line = "CDEF:{$args['0']}={$args[1]}";
			         array_push($this->Args, $line);
			        
			        break;
			    
			    /*
				DEF:vname=rrdfile:ds-name:CF[:step=step][:start=time][:end=time]
				$args[0] = vname
				$args[1] = rrdfile
				$args[2] = ds-name
				$args[3] = CF
				$args[4] = step
				$args[5] = start
				$args[6] = end
				*/
				case "DEF":
						
						$line = "DEF:{$args['0']}={$args[1]}:{$args[2]}:{$args[3]}";
						if ($args[4])
							$line .=":{$args['4']}";
							
						if ($args[5])
							$line .=":{$args['5']}";
							
						if ($args[6])
							$line .=":{$args['6']}";
						
						array_push($this->Args, $line);
							
					break;
				
				/*
				LINE[width]:value[#color][:[legend][:STACK]]
				
				$args[0] = width
				$args[1] = value
				$args[2] = color
				$args[3] = "legend"
				#args[4] = stack
				*/
				case "Line":
					
						$line = "LINE{$args['0']}:{$args['1']}";
						if ($args[2])
							$line .="{$args['2']}";
							
						if ($args[3])
							$line .=":{$args['3']}";
							
						if ($args[4])
							$line .=":{$args['4']}";
						
					
						array_push($this->Args, $line);
						
					break;
				
				/*
				AREA:value[#color][:[legend][:STACK]]
				$args[0] = value
				$args[1] = color
				$args[2] = "legend"
				#args[3] = stack
				*/
				case "Area":
						$line = "AREA:{$args['0']}";
						if ($args[1])
							$line .="{$args['1']}";
							
						if ($args[2])
							$line .=":{$args['2']}";
							
						if ($args[3])
							$line .=":{$args['3']}";
						
							
						array_push($this->Args, $line);
							
					break;
					
				/*
				PRINT:vname:format
				$args[0] = vname
				$args[1] = format
				*/
				case "Print":

						array_push($this->Args, "PRINT:{$args['0']}:{$args['1']}");
	
					break;
				
				/*
				GPRINT:vname:format
				$args[0] = vname
				$args[1] = format
				*/
				case "GPrint":
					
						array_push($this->Args, "GPRINT:{$args['0']}:{$args['1']}:{$args['2']}");
					
					break;
				
				/*
				COMMENT:text
				$args[0] = text
				*/
				case "Comment":
					
						array_push($this->Args, "COMMENT:{$args['0']}");
					
					break;
				
				/*
				VRULE:time#color[:legend]
				$args[0] = time
				$args[1] = color
				$args[2] = legend
				*/	
				case "VRule":
						
						$line = "VRULE:{$args['0']}{$args['1']}";
						if ($args[2])
							$line .= ":{$args['2']}";
						
						array_push($this->Args, $line);
					
					break;
				
				/*
				HRULE:value#color[:legend]
				$args[0] = value
				$args[1] = color
				$args[2] = legend
				*/		
				case "HRule":
					
						$line = "HRULE:{$args['0']}{$args['1']}";
						if ($args[2])
							$line .= ":{$args['2']}";
						
						array_push($this->Args, $line);
					
					break;
				
				/*
				TICK:vname#rrggbb[aa][:fraction[:legend]]
				$args[0] = vname
				$args[1] = color
				$args[2] = alpha
				$args[3] = fraction
				$args[4] = legend
				*/
				case "Tick":
						$line = "TICK:{$args['0']}{$args['1']}{$args['2']}";
						if ($args[3])
							$line .= ":{$args['3']}";
							
						if ($args[4])
							$line .= ":{$args['4']}";
					
						array_push($this->Args, $line);
								
					break;
				
				/*
				SHIFT:vname:offset
				$args[0] = vname
				$args[1] = offset
				*/	
				case "Shift":
					
						array_push($this->Args, "SHIFT:{$args['0']}:{$args['1']}");
					
					break;
			}
		}
	}
?>