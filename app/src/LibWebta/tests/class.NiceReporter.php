<?php

	class NiceReporter extends HtmlReporter {
	    
		function ShowPasses() {
			$this->HtmlReporter();
		}
	    
	    function paintHeader($test_name) {
            $this->sendNoCacheHeaders();
            print "<html>\n<head>\n<title>$test_name</title>\n";
            print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" .
                    $this->_character_set . "\">\n";
            print "<style type=\"text/css\">\n";
            print $this->_getCss() . "\n";
            print "</style>\n";
			echo <<<EO1
					<script type=text/javascript>
					function MM_findObj(n, d) { //v4.01
					var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
						d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
					if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
					for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
					if(!x && d.getElementById) x=d.getElementById(n); return x;
					}
					function showtrace(sid)
					{
						dv = MM_findObj("bt"+sid);
						dv.style.display = "";
					}
					</script>
EO1;
			print "</head>\n<body>\n";
			print "<h1>$test_name</h1>\n";
			flush();
			}
			        
		function paintPass($message) {
			if (PRINTPASSES)
			{
				parent::paintPass($message);
				print "<span class=\"pass\">Pass</span>: ";
				$breadcrumb = $this->getTestList();
				array_shift($breadcrumb);
				print implode("-&gt;", $breadcrumb);
				print "&nbsp;->&nbsp;$message<br />\n";
			}
		}
	    
		function _getCss() {
			return parent::_getCss() . ' .pass { color: green; }';
		}
	}

?>