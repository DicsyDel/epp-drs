{if $status == "Pending"}
	<div style='color:#FF9900'>{t}Pending{/t}</div>
{elseif $status == "Awaiting payment"}
	<div style='color:#FF9900;'>{t}Awaiting payment{/t}</div>
{elseif $status == "Registration pending"}
	<span style='color:#666633;'>{t}Registration pending{/t}</span>
{elseif $status == "Awaiting preregistration"}
	<span style='color:#666633;'>{t}Awaiting pre-registration{/t}</span>
{elseif $status == "Processing"}
	<div style='color:blue;'>{t}Processing{/t}</div>
{elseif $status == "Delegated"}
	<span style='color:green;'>{t}Delegated{/t}</span> {if $admin}<span>[<a href='domains_view.php?task=delete&domainid={$id}'>Delete</a>]</span>{/if}
{elseif $status == "Registration failed"}
	<div style='color:red;'>{t}Registration failed{/t}</div>
{elseif $status == "Rejected"}
	<div style='color:red;'>{t}Rejected{/t}</div>
{elseif $status == "Pending delete"}
	<div style='color:red;'>{t}Pending delete{/t}</div>
{elseif $status == "Deleted"}
	<div style='color:red;'>{t}Deleted{/t}</div>
{elseif $status == "Application pending"}
	<div style='color:#FF9900;'>{t}Application pending{/t}</div>
{elseif $status == "Pending transfer"}
	<div style='color:#FF9900;'>{t}Pending transfer{/t}</div>
{elseif $status == "Pending renewal"}
	<div style='color:#FF9900;'>{t}Pending renewal{/t}</div>
{elseif $status == "Awaiting transfer authorization"}
	<div style='color:#FF9900;'>{t}Awaiting transfer authorization{/t}</div>
{elseif $status == "Transfer failed"}
	<span style='color:red;'>{t}Transfer failed{/t}</span> {if $admin}<span>[<a href='domains_view.php?task=delete&domainid={$id}'>Delete</a>]{/if}</span>
{elseif $status == "Transferred"}
	<span style='color:green;'>{t}Transferred{/t}</span>
{elseif $status == "Transfer requested"}
	<span style='color:#FF9900;'>{t}Transfer requested{/t}</span>
{elseif $status == "Awaiting preregistration"}
	<span style='color:#FF9900;'>{t}Awaiting preregistration{/t}</span>
{elseif $status == "Preregistration delegated"}
	<span style='color:green;'>{t}Delegated (caught){/t}</span>
{elseif $status == "Expired"}
	<span style='color:red;'>{t}Expired{/t}</span>
{elseif $status == "Application recalled"}
	<span style='color:red;'>{t}Application recalled{/t}</span> {if $admin}<span>[<a href='domains_view.php?task=delete&domainid={$id}'>Delete</a>]{/if}</span>
	{else}
	<span style='color:red;'>{t}Unknown{/t}</span> {if $admin}<span>[<a href='domains_view.php?task=delete&domainid={$id}'>Delete</a>]{/if}</span>
{/if}