<select name="search_country_district" id="search_country_district">
{foreach from=$table->rows item=row}
<option value="{$row->rowId}">
 {foreach from=$row->cells item=value}
   {$value}
 {/foreach}
</option>
{/foreach}
</select>
