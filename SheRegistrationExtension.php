<?php

namespace SheRegistrationExtension;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Theme\LessDefinition;

class SheRegistrationExtension extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLessFiles',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectJavascriptFiles',
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Address' => 'onPostDispatchAddress',

        ];
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $table = 's_core_countries_attributes';

        /** @var CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');
        $service->update($table, 'she_zip_code_pattern', 'string', [
            'label' => 'Pattern for zip code validation',
            'displayInBackend' => true,
        ]);
        $service->update($table, 'she_zip_code_title', 'string', [
            'label' => 'Title for zip code validation',
            'translatable' => true,
            'displayInBackend' => true,
        ]);

        $data = [
            [['DE', 'ES', 'IT'], '[0-9]{5}', 'In {countryName} hat die PLZ 5 Stellen.'],
            [['AT', 'BE', 'CH', 'DK', 'LI'], '[0-9]{4}', 'In {countryName} hat die PLZ 4 Zeichen.'],
            [['PT'], '[0-9]{4}-[0-9]{3}', 'In {countryName} hat die PLZ 4 Zeichen, 1 Sonderzeichen(-) und 3 Zeichen.'],
            [['GB'], '([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\\s?[0-9][A-Za-z]{2})', 'In {countryName} hat die PLZ 1 oder 2 Buchstaben gefolgt von einer oder Buchstaben/Zahl und einer Zahl und zwei Buchstaben.'],
            [['SE'], '[0-9]{3} [0-9]{2}', 'In {countryName} hat die PLZ 3 Zeichen und gefolgt von 2 Zeichen.'],
            [['NL'], '[0-9]{4} [0-9]{2}', 'In {countryName} hat die PLZ 4 Zeichen und gefolgt von 2 Zeichen.'],
        ];

        /** @var \Shopware\Bundle\AttributeBundle\Service\DataPersister $service */
        $service = $this->container->get('shopware_attribute.data_persister');
        $connection = $this->container->get('dbal_connection');
        $sql = 'SELECT `id` FROM `s_core_countries` WHERE `countryiso` = ?';

        foreach ($data as $row) {
            foreach ($row[0] as $countryIso) {
                $countryId = $connection->fetchColumn($sql, [$countryIso]);
                if (empty($countryId)) {
                    continue;
                }
                $service->persist([
                    'she_zip_code_pattern' => $row[1],
                    'she_zip_code_title' => $row[2]
                ], $table, $countryId);
            }
        }
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        if ($context->keepUserData()) {
            return;
        }

        /** @var CrudService $service */
        $service = $this->container->get('shopware_attribute.crud_service');
        $service->delete('s_core_countries_attributes', 'she_zip_code_pattern');
        $service->delete('s_core_countries_attributes', 'she_zip_code_title');
    }

    public function onCollectLessFiles(\Enlight_Event_EventArgs $args)
    {
        $shop = $args->get('shop');
        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName($this->getName(), $shop);

        $less = new LessDefinition(
            array_filter($pluginConfig, function ($k) {
                return strpos($k, 'capitalize_') === 0;
            }, ARRAY_FILTER_USE_KEY),
            [$this->getPath() . '/Resources/views/frontend/_public/src/less/all.less'],
            __DIR__
        );

        return new ArrayCollection([$less]);
    }


    public function onCollectJavascriptFiles(\Enlight_Event_EventArgs $args)
    {
        return new ArrayCollection([
            $this->getPath() . '/Resources/views/frontend/_public/src/js/jquery.she-registration-extension.js'
        ]);
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $args->getSubject()->View()->addTemplateDir($this->getPath() . '/Resources/views');
    }


    public function onPostDispatchAddress(\Enlight_Controller_ActionEventArgs $args)
    {
        $subject = $args->getSubject();
        $subject->View()->assign('countryList', $this->getCountriesWithAttributes());
    }

    /**
     * @return array
     */
    private function getCountriesWithAttributes()
    {
        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();
        $service = $this->container->get('shopware_storefront.location_service');
        $countries = $service->getCountries($context);

        $countries = $this->container->get('legacy_struct_converter')->convertCountryStructList($countries);

        return array_map(function ($country) {
            $country['allow_shipping'] = $country['allowShipping'];
            return $country;
        }, $countries);
    }
}
