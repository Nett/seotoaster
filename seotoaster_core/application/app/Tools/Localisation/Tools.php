<?php

class Tools_Localisation_Tools
{
    public static function getActiveLocalList()
    {
        $config          = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $langDefault     = $config->getConfig('language');
        $activeLocalList = $config->getConfig('activeLocalList');
        $langList        = Zend_Controller_Action_HelperBroker::getStaticHelper('language')->getLanguages(false);
        if (empty($activeLocalList)) {
            return isset($langList[$langDefault]) ? array($langDefault => $langList[$langDefault]) : array();
        }

        $activeLocalList = explode(',', $activeLocalList);
        array_unshift($activeLocalList, $langDefault);
        $activeLocalList = array_flip($activeLocalList);

        return array_intersect_key(array_replace($activeLocalList, $langList), $activeLocalList);
    }

    public static function getLangDefault()
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('language');
    }
}
