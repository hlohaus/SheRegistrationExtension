{extends file="parent:frontend/register/billing_fieldset.tpl"}

{block name="frontend_register_billing_fieldset_input_zip_and_city"}
    {include file="frontend/she_zip_code/config_include.tpl"}
    {$smarty.block.parent}
{/block}