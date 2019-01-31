{extends file="parent:frontend/address/form.tpl"}

{block name='frontend_address_form_input_zip_and_city'}
    {include file="frontend/she_zip_code/config_include.tpl"}
    {$smarty.block.parent}
{/block}