
	</td></tr></table>

</td>
		</tr>
		{if !$disable_footer_line}
		<tr class="th">
			<td colspan="{if $colspan}{$colspan}{else}14{/if}"><table border="0" width="100%" class="WebtaTable_Footer">
					<tr>
						<td  colspan="4" align="left">
						{if $cancel_btn}
							<input type="submit" class="btn" name="cancel" value="Cancel" />&nbsp;
						{/if}
						
						{if $prev_page}
							<input type="submit" class="btn" value="Prev" name="back">&nbsp;
						{/if}

						{if $edit_page}
							<input style="vertical-align:middle;" name="Submit" type="submit" class="btn" value="Save">
							<input name="id" type="hidden" id="id" value="{$id}">
						{elseif $search_page}
							<input type="submit" class="btn" value="Search">
						{elseif $page_data_options_add}
							<a href="{$smarty.server.PHP_SELF|replace:"view":"add"}{$page_data_options_add_querystring}">Add new</a>
						{/if}
						{if $next_page}
								&nbsp;<input type="submit" class="btn" name="next" value="Next" />	
						{/if}
						{if $button_js}
								&nbsp;<input id="button_js" style="display:none;vertical-align:middle;" type="button" onclick="{$button_js_action}" class="btn" name="cbtn_2" value="{$button_js_name}" />	
						{/if}
						{if $button2}
								&nbsp;<input type="submit" class="btn" name="cbtn_2" value="{$button2_name}" />	
						{/if}
						{if $retry_btn}
								&nbsp;<input type="button" class="btn" name="retrybtn" value="Retry" onclick="window.location=get_url;return false;" />	
						{/if}
                        {if $backbtn}
								&nbsp;<input type="submit" class="btn" name="cbtn_3" value="Back" onclick="history.back();return false;" />	
						{/if}
						{if $loader}
						    <span style="display:none;" id="btn_loader">
                                <img style="vertical-align:middle;" src="images/snake-loader.gif"> {$loader}
                            </span>
						{/if}
						&nbsp;
						</td>
						<td width="10%" align="right" nowrap>
							<input name="page" type="hidden" id="page" value="{$page}">
							<input name="f" type="hidden" id="f" value="{$f}">
							{if $page_data_options && $page_data_options|@count > 0}
								Selected:
								<select name="action" class="text" style="vertical-align:middle;">
									{section name=id loop=$page_data_options}
								     <option value="{$page_data_options[id].action}">{$page_data_options[id].name}</option> 
								    {/section}
								</select>
								<input type="submit" name="actionsubmit" style="vertical-align:middle;" value="Apply" class="btn">
							{/if}
						
						</td>
						<td width="1" align="left">&nbsp;</td>
					</tr>
			</table></td>
		</tr>
		{/if}
		</table></td>
	<td width="7" class="TableHeaderCenter"></td>
</tr>
<tr>
	<td width="7"><div class="TableFooterLeft"></div></td>
	<td><div class="TableFooterCenter"></div></td>
	<td width="7"><div class="TableFooterRight"></div></td>
</tr>
</table>
	