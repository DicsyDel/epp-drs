<div id="notes{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('notes{$rev}');" style="cursor:pointer;"><img id="notes{$rev}_img" src="images/minus.gif"> Notes</div>
	<ul id="notes{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{section name=id loop=$notes}
		<li>{$notes[id]}</li>
	{/section}
	</ul>
</div>

<div id="changelog{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('changelog{$rev}');" style="cursor:pointer;"><img id="changelog{$rev}_img" src="images/minus.gif"> Changelog</div>
	<ul id="changelog{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$ChangelogAdded}
		<li>[ADDED] {$item}</li>
	{/foreach}
	
	{foreach item=item key=key from=$ChangelogFixed}
		<li>[FIXED] {$item}</li>
	{/foreach}
	</ul>
</div>

{if $FilesToUpdate|@count > 0}
<div id="upfiles{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('upfiles{$rev}');" style="cursor:pointer;"><img id="upfiles{$rev}_img" src="images/minus.gif"> Changed files</div>
	<ul id="upfiles{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$FilesToUpdate}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $FoldersToAdd|@count > 0}
<div id="addfolders{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('addfolders{$rev}');" style="cursor:pointer;"><img id="addfolders{$rev}_img" src="images/minus.gif"> New folders</div>
	<ul id="addfolders{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$FoldersToAdd}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $FilesToAdd|@count > 0}
<div id="addfiles{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('addfiles{$rev}');" style="cursor:pointer;"><img id="addfiles{$rev}_img" src="images/minus.gif"> New files</div>
	<ul id="addfiles{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$FilesToAdd}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $FilesToDelete|@count > 0}
<div id="delfiles{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('delfiles{$rev}');" style="cursor:pointer;"><img id="delfiles{$rev}_img" src="images/minus.gif"> Files to delete</div>
	<ul id="delfiles{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$FilesToDelete}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $FoldersToDelete|@count >0}
<div id="delfolders{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('delfolders{$rev}');" style="cursor:pointer;"><img id="delfolders{$rev}_img" src="images/minus.gif"> Folders to delete</div>
	<ul id="delfolders{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$FoldersToDelete}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $Chmods|@count > 0}
<div id="chmods{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('chmods{$rev}');" style="cursor:pointer;"><img id="chmods{$rev}_img" src="images/minus.gif"> New permissions</div>
	<ul id="chmods{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$Chmods}
		<li>{$key} to {$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $Commands|@count > 0}
<div id="cmd{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('cmd{$rev}');" style="cursor:pointer;"><img id="cmd{$rev}_img" src="images/minus.gif"> Commands</div>
	<ul id="cmd{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$Commands}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $Scripts|@count > 0}
<div id="scripts{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('scripts{$rev}');" style="cursor:pointer;"><img id="scripts{$rev}_img" src="images/minus.gif"> Scripts</div>
	<ul id="scripts{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$Scripts}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}

{if $SQLQueries|@count > 0}
<div id="sql{$rev}" style="margin-bottom:15px;"> <div onclick="ShowHide('sql{$rev}');" style="cursor:pointer;"><img id="sql{$rev}_img" src="images/minus.gif"> SQL Queries</div>
	<ul id="sql{$rev}_ul" style="margin-top:5px;margin-bottom:5px;">
	{foreach item=item key=key from=$SQLQueries}
		<li>{$item}</li>
	{/foreach}
	</ul>
</div>
{/if}