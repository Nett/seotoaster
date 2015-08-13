<?php

class Tools_Localization_Tools
{
    public static function getActiveLanguagesList($langDefault = true)
    {
        $config              = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $activeLanguagesList = $config->getConfig('activeLanguagesList');
        $langList            = Zend_Controller_Action_HelperBroker::getStaticHelper('language')->getLanguages(false);

        $activeLanguagesList = explode(',', $activeLanguagesList);
        if($langDefault){
            $langDefault = $config->getConfig('language');
            array_unshift($activeLanguagesList, $langDefault);
        }
        $activeLanguagesList = array_flip($activeLanguagesList);

        return array_intersect_key(array_replace($activeLanguagesList, $langList), $activeLanguagesList);
    }

    public static function getLangDefault()
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('language');
    }
}
