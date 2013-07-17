<?
    include ("./src/prepend.inc.php");

    
    $smarty->assign(array("reason" => $_SESSION["failure_reason"]));
    $smarty->display("payment_failed.tpl");
?>