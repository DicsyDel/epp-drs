{include file="admin/inc/header.tpl"}

<script language="Javascript">

var oNode = false;
var curFolder = '{$dir}';
var reloadAfterSave = false;

{literal}
function EditTemplate(templateName, dirName, aNode)
{
    DeleteTemplateIcon(false);
    
    $('tarea_table').style.display = "none";
    $('templ_error').style.display = "none";
    $('templ_save').style.display = 'none';
    $('templ_area').style.display = "";
    $('templ_load').style.display = "";
    $('button_js').style.display = "none";
    $('templ_saving').style.display = "none";
        
    $('fdir').value = dirName;
    $('fname').value = templateName;
    
    $('webta_table_headeredit').innerHTML = "<strong>"+templateName+"</strong>";
    
    if (oNode)
    {
        $(oNode).style.border = "1px solid #F4F4F4";
        $(oNode).style.backgroundColor = "#F4F4F4";    
    }
    
        
    $(aNode).style.border = "1px solid gray";
    $(aNode).style.backgroundColor = "white";
    
    oNode = aNode;
    
    new Ajax.Request('/admin/server/tmanager.php', {
			parameters: '_cmd=get_template_content'+ 
						'&tname=' + templateName + '&tdir='+dirName,
			onSuccess: function(response)
			{
			    $('templ_load').style.display = "none";
			    text = response.responseText;
			    if (text == 'false')
			    {
			        $('templ_error').style.display = "";
			        $('templ_error').innerHTML = "Cannot get requested template. Check file permissions.";
			    }
			    else
			    {
			        $('tarea_table').style.display = "";
			        $('fbody').value = text;
			        $('button_js').style.display = "";
			        
			        DeleteTemplateIcon(true);
			    }
			},
			
			onFailure: function(response)
			{
			    $('templ_error').style.display = "";
			    $('templ_error').innerHTML = "Cannot get requested template.";
			}
		});
}

function DeleteTemplateIcon(val)
{
    val = (val) ? "" : "none";
    
    $('delete_icon').style.display = val;
}

function DeleteTemplate()
{
    dirName = $('fdir').value;
    templateName = $('fname').value;
    
    document.location = "templates_manager.php?action=delete&dirName="+dirName+"&templateName="+templateName;
}

function CreateNewTemplate()
{
    templatename = window.prompt("Enter template filename:", "newtemplate.tpl");
    if (templatename)
    {
        $('webta_table_headeredit').innerHTML = "<strong>"+templatename+"</strong>";
        $('fname').value = templatename;
        $('fdir').value = curFolder;
        reloadAfterSave = true;
        
        $('templ_area').style.display = "";
        $('tarea_table').style.display = "";
        $('fbody').value = "";
        $('button_js').style.display = "";
    }
    else
        return;
}

function SaveTemplate()
{
    dirName = $('fdir').value;
    templateName = $('fname').value;
    text = $('fbody').value;
    $('templ_save').style.display = 'none';
    $('templ_saving').style.display = "";
    
    new Ajax.Request('/admin/server/tmanager.php', {
			parameters: '_cmd=set_template_content'+ '&r=' +Math.random()+
						'&tname=' + templateName + '&tdir='+dirName+"&content="+encodeURIComponent(text),
			method:'post',
			onSuccess: function(response)
			{
			    $('templ_load').style.display = "none";
			    text = response.responseText;
			    if (parseInt(text) > 0)
			    {
			        $('templ_save').style.display = '';
			        if (reloadAfterSave)
			        {
			            document.location = "templates_manager.php?dir="+$('fdir').value;
			        }
			    }
			    else
			    {
			        $('templ_error').style.display = "";
			        $('templ_error').innerHTML = "Cannot save template. Please check file permissions and try again.";
			    }
			    
			    $('templ_saving').style.display = "none";
			},
			
			onFailure: function(response)
			{
			    $('templ_error').style.display = "";
			    $('templ_error').innerHTML = "Cannot get requested template.";
			    $('templ_saving').style.display = "none";
			}
		});
    
    
}

</script>
{/literal}
<table width="100%" cellpadding="0" cellspacing="0" height="100%">
    <tr valign="top">
        <td width="30%">
            {include file="admin/inc/table_header.tpl"}
            {include file="admin/inc/intable_header.tpl" header="Templates" color="Gray"}
    	       <tr>
    	           <td>
        	<div style="float:left;overflow:auto;height:484px;width:320px;">
        	<table width="300" cellpadding="4" cellspacing="0" border="0">
        	{section name=id loop=$folders}
        		<tr valign="top" style="height:10px;">
        			<td valign="middle">
        				<span style="vertical-align:middle;">
        					<a href="?dir={$folders[id].name}&cd={$folders[id].curdir}"><img style="vertical-align:middle;" border="0" src="/admin/images/folder.gif"></a> <a href="?dir={$folders[id].name}&cd={$folders[id].curdir}">{$folders[id].name}</a>
        				</span>
        			</td>
        		</tr>
        	{/section}
        	{section name=id loop=$files}
        		<tr valign="top" style="height:10px;">
        			<td valign="middle" style="width:100%">
        				<div id="ff_{$smarty.section.id.iteration}" style="vertical-align:middle; width:290px;;padding:1px;border:1px solid #F4F4F4">
        					<a href="#" onclick="EditTemplate('{$files[id].name}', '{$dir}', 'ff_{$smarty.section.id.iteration}');"><img style="vertical-align:middle;" border="0" alt="{$files[id].type} template file" src="/admin/images/{$files[id].image}.gif"></a> <a href="#" onclick="EditTemplate('{$files[id].name}', '{$dir}', 'ff_{$smarty.section.id.iteration}');">{$files[id].name}</a>
        				</div>
        			</td>
        		</tr>
        	{/section}   
        	<tr>
        		<td>&nbsp;</td>
        	</tr>     
        	</table>
        	</div>
        	<div style="clear:both;"></div>
        	<div style="margin-top:15px;">
        	   <a href="#" onclick="CreateNewTemplate();"><img style="vertical-align:middle;" src="images/add.gif"> Add new template</a>
        	   &nbsp;&nbsp;
        	   <a id="delete_icon" style="display:none;" href="#" onclick="DeleteTemplate();"><img style="vertical-align:middle;" src="images/delete.gif"> Delete selected</a>
        	</div>
        	</td>
    	       </tr>
        	   {include file="admin/inc/intable_footer.tpl" color="Gray"}
        	{include file="admin/inc/table_footer.tpl" disable_footer_line=1}
    	</td>
    	<td width="20">&nbsp;</td>
    	<td width="68%" id="templ_area" style="display:none;">
    	   {include file="admin/inc/table_header.tpl"}
    	   </form>
    	   <form action="templates_manager.php?file={$file}" method="post">
    	       {include file="admin/inc/intable_header.tpl" header="" color="Gray" header_id="edit"}
    	       <tr>
    	           <td width="100%">
                        <div id="tarea_table" style="margin-left:10px;margin-right:10px;height:472px;display:none;width:100%;">
                            <input type="hidden" id="fdir" name="dir" value="{$dir}">
                            <input type="hidden" id="fname" name="explode" value="{$file}">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td><textarea id="fbody" name="body" class="text" style="width:99%;font-size:11px;" rows="32" class="text"></textarea></td>
                                </tr>
                            </table>
                        </div>
                        <div style="height:15px;padding-left:10px;">
                            <div id="templ_save" style="display:none;vertical-align:middle;color:green;">Template successfully saved</div>
                            <div id="templ_error" style="display:none;vertical-align:middle;color:red;"></div>
                            <div id="templ_saving" style="display:none;vertical-align:middle;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> Saving template... Please wait.</div>
                        </div>
            	<div id="templ_load" style="display:none;vertical-align:middle;"><img style="vertical-align:middle;" src="images/snake-loader.gif"> Loading template... Please wait.</div>
            	</td>
    	       </tr>
        	   {include file="admin/inc/intable_footer.tpl" color="Gray"}
    	   {include file="admin/inc/table_footer.tpl" button_js=1 button_js_name="Save" button_js_action="SaveTemplate();"}
    	</td>
    </tr>
</table>	
{include file="admin/inc/footer.tpl"}