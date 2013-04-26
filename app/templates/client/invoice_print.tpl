<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{$servicename} {t}printable invoice{/t}</title>
</head>
<body onload="window.print();">
<table width="100%" border="0">
  <tr>
    <td valign="top">
	    {if $logo_exists}
	    <img src="/images/logo.gif" /><br/>
	    {else}
	        {$servicename}<br />
		    {$supportemail}
	    {/if}
	</td>
    <td valign="top">{t}Invoice for:{/t}<br />
      {$client.name}<br />
      {$client.address}<br />
      {$client.email}</td>
  </tr>
</table>
<br />
<h1>{t}Order No.{/t} {$orderid}
  <br />
</h1>
<table width="100%" cellpadding="8">
<tr>
  <td valign="top" nowrap="nowrap"><strong>{t}Invoice ID{/t}</strong></td>
  <td valign="top" nowrap="nowrap"><strong>{t}Invoice date{/t}</strong></td>
	<td valign="top"><strong>{t}Payment for{/t}</strong></td>
	<td align="right" valign="top" nowrap="nowrap"><strong>{t}Amount{/t}</strong></td>
</tr>
{section name=id loop=$invoices}
<tr>
  <td valign="top" nowrap="nowrap">{$invoices[id]->CustomID}</td>
  <td valign="top" nowrap="nowrap">{$invoices[id]->CreatedAt|date_format:"%D %T"}</td>
	<td valign="top"><em>{$invoices[id]->Description}</em></td>
	<td align="right" valign="top" nowrap="nowrap">{$Currency} {$invoices[id]->GetTotal()|string_format:"%.2f"}
	{if $invoices[id]->GetVATPercent()}(Incl. VAT {$invoices[id]->GetVATPercent()|string_format:"%.2f"}%){/if}</td>
</tr>
{/section}
<tr>
  <td colspan="4" align="left" valign="top" nowrap="nowrap"><hr/></td>
  </tr>
<tr>
  <td align="left" valign="top" nowrap="nowrap">&nbsp;</td>
  <td align="left" valign="top" nowrap="nowrap">&nbsp;</td>
	<td align="left" valign="top" nowrap="nowrap"><strong>{t}Total payable{/t} {if $vat > 0}{t vat=$vat}(Incl. VAT %1%){/t}{/if}:</strong></td>
    <td align="right" valign="top" nowrap="nowrap"><strong>{$Currency} {$total|string_format:"%.2f"}</strong></td>
</tr>
</table>
<br />
<br />
</body>
</html>
