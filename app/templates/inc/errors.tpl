{if $errors != false}
<div style="width: 600px;color:red; " align="left">
{t}Please fix the following errors and try again:{/t}
	<ul style="text-align:left; color:red;">
		{section name=id loop=$errors}
		<li>{$errors[id]}</li>
		{/section}
	</ul>
</div>
{/if}