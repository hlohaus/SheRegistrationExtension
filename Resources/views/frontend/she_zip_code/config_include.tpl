{if {config name="zip_code_validation" namespace="SheRegistrationExtension"}}
    {foreach $countryList as $country}
        {if $country && $country.attributes && $country.attributes.core}
            {$pattern = $country.attributes.core->get('she_zip_code_pattern')}
            {$title = $country.attributes.core->get('she_zip_code_title')}
            {if $pattern}
                <div data-sheZipCodeCountryId="{$country.id}" data-pattern="{$pattern|escape}" data-title="{$title|escape}"></div>
            {/if}
        {/if}
    {/foreach}
{/if}