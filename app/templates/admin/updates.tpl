{include file="admin/inc/header.tpl"}
	{literal}
	<script language="Javascript">		
		function ShowHide(section)
		{
			var obj = $(section+'_ul');
			if (obj.style.display == "")
			{
				obj.style.display = "none";
				$(section+'_img').src = "images/plus.gif";
			}
			else
			{
				obj.style.display = "";
				$(section+'_img').src = "images/minus.gif";
			}
		}
		
		var checked = 0;
		
		function SetCheck(obj)
		{
			num = ($('ignore_requirements')) ? 3 : 2;
			if (obj.checked == true)
				checked++;
			else
				checked--;
				
			if (checked == num)
				$('update_btn').disabled = false;
			else
				$('update_btn').disabled = true;
		}
	</script>
	{/literal}
	{include file="admin/inc/table_header.tpl" nofilter=1}
		{if !$isinfo}
			{include file="admin/inc/intable_header.tpl" header="New updates" color="Gray"}
			<tr>
				<td colspan="2">
					New updates available! You may schedule automatic installation of this updates below.<br>
					Your current version: {$curr_revision}<br>
					This update will upgrade your copy to version {$latest_rev}.<br>
					<br>
					Updates that will be installed:<br><br>
					<table width="400">
						<tr>
							<td><b>Version</b></td>
							<td><b>Published at</b></td>
						</tr>
						{foreach item=revobj key=rev from=$hops}
						<tr>
							<td>{$revobj->Revision}</td>
							<td>{$revobj->DateReleased}</td>
						</tr>
						{/foreach}
					</table>
					{if $r_errors|@count > 0}
					<br />
					<div style='border:1px solid red;width:100%;background-color:white;'>
						<div style='padding:6px;float:left;width:100%;'>
							<img src='/admin/images/icon_warn.gif'> <span style="font-weight:bold;">Missing requirements detected</span>
						</div>
						<div style='float:left;padding-left:44px;width:100%;'>
						{foreach item=errors key=revision from=$r_errors}
							<ul style="margin-left:0px;padding-left:0px;">
								<li>Version {$revision}:
									{section name=id loop=$errors}
										<li type="circle" style="margin-left:15px;">{$errors[id].0} {if $errors[id].1}(<a href="{$errors[id].1}" target="_blank">More info</a>){/if}</li>
									{/section}
								</li>
							</ul>
						{/foreach}
						</div>
						<div slyle="clear:both;"></div>
						
						{if !$mandatory_requirement_missing}
						<div style="padding:6px;">
							<input type="checkbox" onclick="SetCheck(this)" id="ignore_requirements" name="ignore_requirements" value="1" /> Ignore these requirements and schedule updates anyway.
						</div>
						{/if}
					</div>
					{/if}
					
					{if !$mandatory_requirement_missing}
					<br />
					<input type="checkbox" onclick="SetCheck(this)" name="agree" value="1"> Yes, I want these updates to be automatically installed upon next cronjob run.<br>
					<input type="checkbox" onclick="SetCheck(this)" name="agree2" value="1"> Yes, I understand that some of my files can be overwritten during update. I am responsible to making backups of any files that I changed. This includes templates, language files CSS files, images, Javascript files. A list of all updated files can be found below in <b>Cumulative details</b> section.
					<br /><br />
					<input class="btn" name="but" disabled="true" id="update_btn" type="submit" value="Schedule updates">
					{/if}
				</td>
			</tr>
			{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{else}
			{include file="admin/inc/intable_header.tpl" header="Information on installed update" color="Gray"}
			<tr>
				<td colspan="2">
					Updates that were installed:<br><br>
					<table width="400">
						<tr>
							<td><b>Version</b></td>
							<td><b>Published at</b></td>
						</tr>
						{foreach item=revobj key=rev from=$hops}
						<tr>
							<td>{$revobj->Revision}</td>
							<td>{$revobj->DateReleased}</td>
						</tr>
						{/foreach}
					</table>
				</td>
			</tr>
			{include file="admin/inc/intable_footer.tpl" color="Gray"}
		{/if}
		{include file="admin/inc/intable_header.tpl" header="Cumulative details" color="Gray"}
		<tr>
			<td colspan="2">
				{if !$isinfo}
				{include file="admin/inc/update_information.tpl"}
				{else}
				
					{foreach item=revobj key=rev from=$hops}
					<div id="rev_{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('rev_{$rev}');" style="cursor:pointer;"><img id="rev_{$rev}_img" src="images/minus.gif"> Release {$rev}</div>
						<div id="rev_{$rev}_ul" style="margin-top:5px;margin-bottom:5px;padding-left:20px;">
							{php}$this->_tpl_vars['notes'] = array($this->_tpl_vars['revobj']->Notes);{/php}
							{assign var=ChangelogAdded  value=$revobj->ChangelogAdded}
							{assign var=ChangelogFixed  value=$revobj->ChangelogFixed}
							{assign var=FilesToUpdate  value=$revobj->FilesToUpdate}
							{assign var=FoldersToAdd  value=$revobj->FoldersToAdd}
							{assign var=FilesToAdd  value=$revobj->FilesToAdd}
							{assign var=FilesToDelete  value=$revobj->FilesToDelete}
							{assign var=FoldersToDelete  value=$revobj->FoldersToDelete}
							{assign var=Chmods  value=$revobj->Chmods}
							{assign var=Commands  value=$revobj->Commands}
							{assign var=Scripts  value=$revobj->Scripts}
							{assign var=SQLQueries  value=$revobj->SQLQueries}
							{include file="admin/inc/update_information.tpl"}
						</div>
					</div>
					{/foreach}
				{/if}
			</td>
		</tr>
        {include file="admin/inc/intable_footer.tpl" color="Gray"}
    {include file="admin/inc/table_footer.tpl" disable_footer_line=1}
{include file="admin/inc/footer.tpl"}