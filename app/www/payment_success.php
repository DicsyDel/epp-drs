<?
    include ("./src/prepend.inc.php");
        
    switch ($req_op)
    {
    	case INVOICE_PURPOSE::DOMAIN_CREATE:
    		
    		$template_name = "payment_success_reg.tpl";
    		
    		break;
    		
    	case INVOICE_PURPOSE::DOMAIN_TRANSFER:
    		
    		$template_name = "payment_success_transfer.tpl";
    		
    		break;
    		
    	default:
    		
    		$template_name = "payment_success_common.tpl";
    		
    		break;
    }
    
    $smarty->display($template_name);
?>