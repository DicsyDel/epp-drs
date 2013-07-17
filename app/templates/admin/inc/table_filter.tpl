<div>
	<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td width="7"><div class="TableHeaderLeft"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td><div class="TableHeaderCenter"></div></td>
			<td width="7"><div class="TableHeaderRight"></div></td>
		</tr>
		<tr bgcolor="#C3D9FF">
			<td width="7" class="TableHeaderCenter"></td>
			<td nowrap>
				&nbsp;
				<input name="filter_q" type="text" class="text" id="filter_q" value="{$filter_q}">
			</td>
			<td align="left" nowrap>
					&nbsp;
					<input name="Submit" type="submit" class="btn{if $filter_q}i{else}{/if}" value="Filter">
					<input name="act" type="hidden" id="act" value="filter1">
					&nbsp;
			</td>
			<td width="7" class="TableHeaderCenter"></td>
		</tr>
	</table>
</div>