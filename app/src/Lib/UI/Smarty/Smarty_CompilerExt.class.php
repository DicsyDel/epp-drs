<?
	class Smarty_CompilerExt extends Smarty_Compiler 
	{
		function __construct()
		{
			parent::Smarty_Compiler();
		}
		
		/**
	     * Compiles references of type $smarty.foo
	     *
	     * @param string $indexes
	     * @return string
	     */
	    function _compile_smarty_ref(&$indexes)
	    {
	        /* Extract the reference name. */
	        $_ref = substr($indexes[0], 1);
	        foreach($indexes as $_index_no=>$_index) {
	            if (substr($_index, 0, 1) != '.' && $_index_no<2 || !preg_match('~^(\.|\[|->)~', $_index)) {
	                $this->_syntax_error('$smarty' . implode('', array_slice($indexes, 0, 2)) . ' is an invalid reference', E_USER_ERROR, __FILE__, __LINE__);
	            }
	        }
	
	        switch ($_ref) 
	        {
	        	case 'class_const':
	        		array_shift($indexes);
	        		
	        		$_class_name = substr(array_shift($indexes), 1);
	        		$_const_name = substr(array_shift($indexes), 1);
	        		
	        		if (!class_exists($_class_name))
	        			$this->_syntax_error('$smarty' . implode('', array_slice($indexes, 0, 2)) . ' is an invalid reference', E_USER_ERROR, __FILE__, __LINE__);
	        		
	        		$refl = new ReflectionClass($_class_name);
	        		
	        		if (!$refl->hasConstant($_const_name))
	        			$this->_syntax_error('$smarty' . implode('', array_slice($indexes, 0, 2)) . ' is an invalid reference', E_USER_ERROR, __FILE__, __LINE__);
	        		
	        		$compiled_ref = "'{$refl->getConstant($_const_name)}'";
	        		
	        		break;
	        	
	        	case 'now':
	                $compiled_ref = 'time()';
	                $_max_index = 1;
	                break;
	
	            case 'foreach':
	                array_shift($indexes);
	                $_var = $this->_parse_var_props(substr($indexes[0], 1));
	                $_propname = substr($indexes[1], 1);
	                $_max_index = 1;
	                switch ($_propname) {
	                    case 'index':
	                        array_shift($indexes);
	                        $compiled_ref = "(\$this->_foreach[$_var]['iteration']-1)";
	                        break;
	                        
	                    case 'first':
	                        array_shift($indexes);
	                        $compiled_ref = "(\$this->_foreach[$_var]['iteration'] <= 1)";
	                        break;
	
	                    case 'last':
	                        array_shift($indexes);
	                        $compiled_ref = "(\$this->_foreach[$_var]['iteration'] == \$this->_foreach[$_var]['total'])";
	                        break;
	                        
	                    case 'show':
	                        array_shift($indexes);
	                        $compiled_ref = "(\$this->_foreach[$_var]['total'] > 0)";
	                        break;
	                        
	                    default:
	                        unset($_max_index);
	                        $compiled_ref = "\$this->_foreach[$_var]";
	                }
	                break;
	
	            case 'section':
	                array_shift($indexes);
	                $_var = $this->_parse_var_props(substr($indexes[0], 1));
	                $compiled_ref = "\$this->_sections[$_var]";
	                break;
	
	            case 'get':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_GET' : "\$GLOBALS['HTTP_GET_VARS']";
	                break;
	
	            case 'post':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_POST' : "\$GLOBALS['HTTP_POST_VARS']";
	                break;
	
	            case 'cookies':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_COOKIE' : "\$GLOBALS['HTTP_COOKIE_VARS']";
	                break;
	
	            case 'env':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_ENV' : "\$GLOBALS['HTTP_ENV_VARS']";
	                break;
	
	            case 'server':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_SERVER' : "\$GLOBALS['HTTP_SERVER_VARS']";
	                break;
	
	            case 'session':
	                $compiled_ref = ($this->request_use_auto_globals) ? '$_SESSION' : "\$GLOBALS['HTTP_SESSION_VARS']";
	                break;
	
	            /*
	             * These cases are handled either at run-time or elsewhere in the
	             * compiler.
	             */
	            case 'request':
	                if ($this->request_use_auto_globals) {
	                    $compiled_ref = '$_REQUEST';
	                    break;
	                } else {
	                    $this->_init_smarty_vars = true;
	                }
	                return null;
	
	            case 'capture':
	                return null;
	
	            case 'template':
	                $compiled_ref = "'$this->_current_file'";
	                $_max_index = 1;
	                break;
	
	            case 'version':
	                $compiled_ref = "'$this->_version'";
	                $_max_index = 1;
	                break;
	
	            case 'const':
	                if ($this->security && !$this->security_settings['ALLOW_CONSTANTS']) {
	                    $this->_syntax_error("(secure mode) constants not permitted",
	                                         E_USER_WARNING, __FILE__, __LINE__);
	                    return;
	                }
	                array_shift($indexes);
	                if (preg_match('!^\.\w+$!', $indexes[0])) {
	                    $compiled_ref = '@' . substr($indexes[0], 1);
	                } else {
	                    $_val = $this->_parse_var_props(substr($indexes[0], 1));
	                    $compiled_ref = '@constant(' . $_val . ')';
	                }
	                $_max_index = 1;
	                break;
	
	            case 'config':
	                $compiled_ref = "\$this->_config[0]['vars']";
	                $_max_index = 3;
	                break;
	
	            case 'ldelim':
	                $compiled_ref = "'$this->left_delimiter'";
	                break;
	
	            case 'rdelim':
	                $compiled_ref = "'$this->right_delimiter'";
	                break;
	                
	            default:
	                $this->_syntax_error('$smarty.' . $_ref . ' is an unknown reference', E_USER_ERROR, __FILE__, __LINE__);
	                break;
	        }
	
	        if (isset($_max_index) && count($indexes) > $_max_index) {
	            $this->_syntax_error('$smarty' . implode('', $indexes) .' is an invalid reference', E_USER_ERROR, __FILE__, __LINE__);
	        }
	
	        array_shift($indexes);
	        return $compiled_ref;
	    }
		
		function _compile_file($resource_name, $source_content, &$compiled_content)
		{
			if (parent::_compile_file($resource_name, $source_content, &$compiled_content))
			{
				$open_tag_replace_to = "\nEOT;\n";
				$close_tag_replace_to = "echo <<<EOT\n";
						
				$content = preg_replace("/\?>/si", $close_tag_replace_to, preg_replace("/<\?(php)?/si", $open_tag_replace_to, trim($compiled_content), -1, &$c_open), -1, &$c_close)."\n";
											
				if ($c_open == $c_close)
					$content .= "\nEOT;\n";
					
				$compiled_content = $content;
					
				return true;
			}
		}
				
		function _compile_tag($template_tag)
	    {
	        /* Matched comment. */
	        if (substr($template_tag, 0, 1) == '*' && substr($template_tag, -1) == '*')
	            return '';
	        
	        /* Split tag into two three parts: command, command modifiers and the arguments. */
	        if(! preg_match('~^(?:(' . $this->_num_const_regexp . '|' . $this->_obj_call_regexp . '|' . $this->_var_regexp
	                . '|\/?' . $this->_reg_obj_regexp . '|\/?' . $this->_func_regexp . ')(' . $this->_mod_regexp . '*))
	                      (?:\s+(.*))?$
	                    ~xs', $template_tag, $match)) {
	            $this->_syntax_error("unrecognized tag: $template_tag", E_USER_ERROR, __FILE__, __LINE__);
	        }
	        
	        $tag_command = $match[1];
	        $tag_modifier = isset($match[2]) ? $match[2] : null;
	        $tag_args = isset($match[3]) ? $match[3] : null;
	
	        if (preg_match('~^' . $this->_num_const_regexp . '|' . $this->_obj_call_regexp . '|' . $this->_var_regexp . '$~', $tag_command)) {
	            /* tag name is a variable or object */
	            $_return = $this->_parse_var_props($tag_command . $tag_modifier);
	            /*return "<?php echo $_return; ?>" . $this->_additional_newline;*/
	            return "<?php echo $_return; ?>";
	        }
	
	        /* If the tag name is a registered object, we process it. */
	        if (preg_match('~^\/?' . $this->_reg_obj_regexp . '$~', $tag_command)) {
	            return $this->_compile_registered_object_tag($tag_command, $this->_parse_attrs($tag_args), $tag_modifier);
	        }
	
	        switch ($tag_command) {
	            case 'include':
	                return $this->_compile_include_tag($tag_args);
	
	            case 'include_php':
	                return $this->_compile_include_php_tag($tag_args);
	
	            case 'if':
	                $this->_push_tag('if');
	                return $this->_compile_if_tag($tag_args);
	
	            case 'else':
	                list($_open_tag) = end($this->_tag_stack);
	                if ($_open_tag != 'if' && $_open_tag != 'elseif')
	                    $this->_syntax_error('unexpected {else}', E_USER_ERROR, __FILE__, __LINE__);
	                else
	                    $this->_push_tag('else');
	                return '<?php else: ?>';
	
	            case 'elseif':
	                list($_open_tag) = end($this->_tag_stack);
	                if ($_open_tag != 'if' && $_open_tag != 'elseif')
	                    $this->_syntax_error('unexpected {elseif}', E_USER_ERROR, __FILE__, __LINE__);
	                if ($_open_tag == 'if')
	                    $this->_push_tag('elseif');
	                return $this->_compile_if_tag($tag_args, true);
	
	            case '/if':
	                $this->_pop_tag('if');
	                return '<?php endif; ?>';
	
	            case 'capture':
	                return $this->_compile_capture_tag(true, $tag_args);
	
	            case '/capture':
	                return $this->_compile_capture_tag(false);
	
	            case 'ldelim':
	                return $this->left_delimiter;
	
	            case 'rdelim':
	                return $this->right_delimiter;
	
	            case 'section':
	                $this->_push_tag('section');
	                return $this->_compile_section_start($tag_args);
	
	            case 'sectionelse':
	                $this->_push_tag('sectionelse');
	                return "<?php endfor; else: ?>";
	                break;
	
	            case '/section':
	                $_open_tag = $this->_pop_tag('section');
	                if ($_open_tag == 'sectionelse')
	                    return "<?php endif; ?>";
	                else
	                    return "<?php endfor; endif; ?>";
	
	            case 'foreach':
	                $this->_push_tag('foreach');
	                return $this->_compile_foreach_start($tag_args);
	                break;
	
	            case 'foreachelse':
	                $this->_push_tag('foreachelse');
	                return "<?php endforeach; else: ?>";
	
	            case '/foreach':
	                $_open_tag = $this->_pop_tag('foreach');
	                if ($_open_tag == 'foreachelse')
	                    return "<?php endif; unset(\$_from); ?>";
	                else
	                    return "<?php endforeach; endif; unset(\$_from); ?>";
	                break;
	
	            case 'strip':
	            case '/strip':
	                if (substr($tag_command, 0, 1)=='/') {
	                    $this->_pop_tag('strip');
	                    if (--$this->_strip_depth==0) { /* outermost closing {/strip} */
	                        $this->_additional_newline = "\n";
	                        return '{' . $tag_command . '}';
	                    }
	                } else {
	                    $this->_push_tag('strip');
	                    if ($this->_strip_depth++==0) { /* outermost opening {strip} */
	                        $this->_additional_newline = "";
	                        return '{' . $tag_command . '}';
	                    }
	                }
	                return '';
	
	            case 'php':
	                /* handle folded tags replaced by {php} */
	                list(, $block) = each($this->_folded_blocks);
	                $this->_current_line_no += substr_count($block[0], "\n");
	                /* the number of matched elements in the regexp in _compile_file()
	                   determins the type of folded tag that was found */
	                switch (count($block)) {
	                    case 2: /* comment */
	                        return '';
	
	                    case 3: /* literal */
	                        return "<?php echo '" . strtr($block[2], array("'"=>"\'", "\\"=>"\\\\")) . "'; ?>";
	
	                    case 4: /* php */
	                        if ($this->security && !$this->security_settings['PHP_TAGS']) {
	                            $this->_syntax_error("(secure mode) php tags not permitted", E_USER_WARNING, __FILE__, __LINE__);
	                            return;
	                        }
	                        return '<?php ' . $block[3] .' ?>';
	                }
	                break;
	
	            case 'insert':
	                return $this->_compile_insert_tag($tag_args);
	
	            default:
	                if ($this->_compile_compiler_tag($tag_command, $tag_args, $output)) {
	                    return $output;
	                } else if ($this->_compile_block_tag($tag_command, $tag_args, $tag_modifier, $output)) {
	                    return $output;
	                } else if ($this->_compile_custom_tag($tag_command, $tag_args, $tag_modifier, $output)) {
	                    return $output;                    
	                } else {
	                    $this->_syntax_error("unrecognized tag '$tag_command'", E_USER_ERROR, __FILE__, __LINE__);
	                }
	
	        }
	    }
	}
?>