<?php

class Widgets_Language_Language extends Widgets_Abstract
{
    protected $_cacheable = false;

    protected function _init()
    {
        parent::_init();
        $this->_view    = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $this->_request = Zend_Controller_Front::getInstance()->getRequest();
    }

    protected function _load()
    {
        $this->_view->langList = Tools_Localisation_Tools::getActiveLocalList();
        if (sizeof($this->_view->langList) <= 1) {
            return '';
        }

        $this->_view->urls = Application_Model_Mappers_PageMapper::getInstance()
            ->getCurrentPageLocalData($this->_toasterOptions['defaultLangId']);
        if (sizeof($this->_view->urls) <= 1) {
            return '';
        }

        if (isset($this->_view->urls[$this->_toasterOptions['lang']]['url'])
            && $this->_view->urls[$this->_toasterOptions['lang']]['url'] === $this->_toasterOptions['url']
        ) {
            $this->_view->activeLang = $this->_toasterOptions['lang'];
        }
        elseif (null !== ($userLang = $this->_request->getCookie('userLanguage'))) {
            $this->_view->activeLang = Zend_Locale::getLocaleToTerritory($userLang);
        }
        else {
            $this->_view->activeLang = Zend_Locale::getLocaleToTerritory(Tools_Localisation_Tools::getLangDefault());
        }

        return $this->_view->render('select.lang.phtml');
    }
}
