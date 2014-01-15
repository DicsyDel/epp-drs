<?php
	require_once (dirname(__FILE__) . "/../../src/prepend.inc.php");
	header("Content-type: text/javascript; charset=utf-8");
?>

Ext.apply(Ext.ux.webta.GridViewer.prototype.messages, {
	pageSize: "<?=_("{0} items per page")?>",
	options: "<?=_("Options")?>",
	tickTrue: "<?=_("Yes")?>",
	tickFalse: "<?=_("No")?>",
	withSelected: "<?=_("With selected")?>",
	blankSelection: "<?=_("Please select at least one item")?>",
	filter: "<?=_("Filter")?>"	
});


Ext.apply(Ext.ux.webta.GridViewer.columnRenderers.messages.status, {
	delegated: "<?=_("Delegated")?>",
	delegatedCaught: "<?=_("Delegated (caught)")?>",
	transferred: "<?=_("Transferred")?>",
	awaitingPreregistration: "<?=_("Awaiting pre-registration")?>",
	awaitingPayment: "<?=_("Awaiting payment")?>",
	awaitingTransferAuthorization: "<?=_("Awaiting transfer authorization")?>",
	registrationPending: "<?=_("Registration pending")?>",
	registrationFailed: "<?=_("Registration failed")?>",
	transferFailed: "<?=_("Transfer failed")?>",
	rejected: "<?=_("Rejected")?>",
	pendingDelete: "<?=_("Pending delete")?>",
	deleted: "<?=_("Deleted")?>",
	pending: "<?=_("Pending")?>",
	transferRequested: "<?=_("Transfer requested")?>",
	expired: "<?=_("Expired")?>"
});