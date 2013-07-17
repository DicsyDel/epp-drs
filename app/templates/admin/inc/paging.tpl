{if $pages[1]}
<div class="paging">
{if $title}{$title}:{/if}&nbsp;
	{if $links}
		{if $prevlink}<div class="tab"><a href="{$prevlink}">Previous</a></div>{/if}
	{/if}
		{if $firstpage}
		<div class="tabPage"><a href="{$firstpage.link}">{$firstpage.num}</a></div><div style="float: left;">&nbsp;...&nbsp;</div>
		{/if}
			{section name=id loop=$pages}
				 {if $pages[id].selected}<div class="tabActive">{$pages[id].num}</div>{else}			
					  <div class="tabPage"><a href="{$pages[id].link}" class="paging_page">{$pages[id].num}</a></div>
				 {/if}	
			{/section}
		{if $lastpage}
		<div style="float: left;">&nbsp;...&nbsp;</div><div class="tabPage"><a href="{$lastpage.link}">{$lastpage.num}</a></div>
		{/if}
	{if $links}
		{if $nextlink}<div class="tab"><a href="{$nextlink}">Next</a></div>{/if}
	{/if}
</div>
{/if}