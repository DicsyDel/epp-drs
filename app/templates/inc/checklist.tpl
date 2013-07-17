
     		<div class="w-checklist">
     			<div class="w-checklist-sidebar w-checklist-sidebar-left">
     				<div class="w-checklist-sidebar-inner">
      				<span class="w-checklist-label">{$checklist.source_title|default:"Select items"}:</span>
      				<ul class="w-checklist-list">
      					{foreach from=$checklist.items item=_item key=_key}
      					<li>
      						<label><input type="checkbox" name="{$checklist.name}" value="{$_key}">{$_item}</label>
      					</li>
      					{/foreach}
      				</ul>
     				</div>
     			</div>
     			<div class="w-checklist-sidebar w-checklist-sidebar-right">
     			    <div class="w-checklist-sidebar-inner">
      				<span class="w-checklist-label">{$checklist.selected_title|default:"Selected"}:</span>
      				<ul class="w-checklist-list" />
     			    </div>
     			</div>
     		</div>