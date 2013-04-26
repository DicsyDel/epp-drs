{include file="admin/inc/header.tpl" noheader=1}
{literal}
<script language="Javascript" type="text/javascript">

function CheckType(type)
{
    if (type == 'SELECT')
    {
        $('selectinfo').style.display = '';
    }
    else
    {
        $('selectinfo').style.display = 'none';
    }
}

{/literal}
var Items = new Array();
var K = {$k|default:-1};
var Num = {$num|default:0};
{literal}

function AddItem()
{
    if ($('ikey').value == '' || $('iname').value == '')
        return "";
    
    Items[K++] = [$('ikey').value, $('iname').value];
    Num++;
    
    cont = document.createElement("DIV");
    cont.style.width = '490px';
    
    dv_key = document.createElement("DIV");
    dv_key.className = 'item_key';
    dv_key.innerHTML = $('ikey').value;
    cont.appendChild(dv_key);
    
    dv_name = document.createElement("DIV");
    dv_name.className = 'item_value';
    dv_name.innerHTML = $('iname').value;
    cont.appendChild(dv_name);
    
    img = document.createElement("IMG");
    img.style.verticalAlign = 'middle';
    img.src = "images/delete_zone.gif";
    img.id = K-1;
    
    dv_img = document.createElement("DIV");
    dv_img.className = 'item_delete';
    dv_img.appendChild(img);
    cont.appendChild(dv_img);
    
    img.onclick = function()
    {
         Num--;
         Items[this.id] = false;
         this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);
         
         if (Num == 0)
         {
            $('no_items').style.display = '';    
         }
    }
    
    $('Items').appendChild(cont);
    $('no_items').style.display = 'none';
    
    $('iname').value = "";
    $('ikey').value = "";
}

function PrepareSubmit()
{   
    for (key in Items)
    {
        if (Items[key] != false && Items[key][0] && Items[key][1])
        {
            inp = document.createElement("INPUT");
            inp.type = 'hidden';
            inp.name = 'select_values[]';
            inp.value = Items[key][0];
            
            $('hidden_container').appendChild(inp);
            
            inp = document.createElement("INPUT");
            inp.type = 'hidden';
            inp.name = 'select_text[]';
            inp.value = Items[key][1];
            
            $('hidden_container').appendChild(inp);
        }
    }
    
    return true;
}
</script>
<style>

.item_key
{
    padding:2px;float:left;width:150px;
}

.item_value
{
    padding:2px;float:left;width:200px;
}

.item_delete
{
    padding:2px;float:left;width:10px;
    cursor:pointer;
}

</style>
{/literal}
	<form action="" method="post" onsubmit="return PrepareSubmit();" style="margin:0px; padding:0px;">
	<div id="hidden_container" style="display:none;"></div>
	{include file="admin/inc/table_header.tpl"}
        <div id="hidden_container" style="display:none;"></div>
		{include file="admin/inc/intable_header.tpl" header="General" color="Gray"}
		<tr>
			<td nowrap="nowrap">Field title:</td>
			<td><input type="text" name="title" class="text" id="name" value="{$ftitle}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Field name:</td>
			<td><input type="text" name="name" class="text" id="name" value="{$name}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Default value:</td>
			<td><input type="text" name="defval" class="text" id="defval" value="{$defval}" size="20" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Required field:</td>
			<td><input type="checkbox" {if $required == 1}checked{/if} name="isrequired" id="isrequired" value="1" /></td>
		</tr>
		<tr>
			<td nowrap="nowrap">Field type:</td>
			<td>
			     <select name="type" class="text" onChange="CheckType(this.value);">
			         <option {if $type == 'TEXT'}selected="selected"{/if} value="TEXT">Text</option>
			         <option {if $type == 'BOOL'}selected="selected"{/if} value="BOOL">Checkbox</option>
			         <option {if $type == 'SELECT'}selected="selected"{/if} value="SELECT">Dropdown</option>
			     </select>
			</td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
		{include file="admin/inc/intable_header.tpl" header="Dropdown items" color="Gray" intableid="selectinfo" visible="$ftable_display"}
		<tr>
			<td colspan="2">
			     <div style="margin-left:15px;padding:2px;">
			         <div id="item1" style="width:420px;padding-left:0px;">
		                 <div style="padding:2px;float:left;width:150px;"><b>Value</b></div>
		                 <div style="padding:2px;float:left;width:200px;"><b>Name</b></div>
		                 <div style="padding:2px;float:left;width:10px;"><b>Delete</b></div>
			         </div>
			         <div id="Items" style="margin-left:0px;width:360px;">
			             <div id="no_items" align="center" style="display:;">No items defined</div>
			             {foreach from=$SelectItems.0 item=item key=key}
			             <div id="{$key}" style="width:490px;" align="center">
    			             <div align="left" class="item_key">{$item}</div>
    			             <div align="left" class="item_value">{$SelectItems.1[$key]}</div>
    			             <div class="item_delete"><img id="img_{$item}" style="vertical-align:middle;" src="images/delete_zone.gif" /></div>
			             </div>
			             <script language="Javascript">
			                 Items[K++] = ['{$item}', '{$SelectItems.1[$key]}'];
			                 Num++;
			                 $('no_items').style.display = 'none';
			                 
			                 o = $('img_{$item}');
			                 
			                 o.id = K-1;
			                 
			                 {literal}
			                 o.onclick = function(){
			                     Num--;
                                 Items[this.id] = false;
                                 this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);
                                 
                                 if (Num == 0)
                                 {
                                    $('no_items').style.display = '';    
                                 }
			                 }
			                 {/literal}
			             </script>
			             {/foreach}
			         </div>
			     </div>
			     <div style="clear:both;"></div>
			     <div style="margin-left:12px;padding:2px;width:380px;">
			         <div style="padding:2px;float:left;width:150px;"><input style="width:100px;" type="text" class="text" id="ikey" value=""></div>
			         <div style="padding:2px;float:left;width:200px;"><input style="width:100px;" type="text" class="text" id="iname" value=""></div>
			         <div style="padding:2px;float:left;width:10px;"><input onclick="AddItem();" type="button" class="btn" id="iname" value="Add"></div>
			     </div>
			</td>
		</tr>
		{include file="admin/inc/intable_footer.tpl" color="Gray"}
		
	{include file="admin/inc/table_footer.tpl" edit_page=1}
{include file="admin/inc/footer.tpl"}