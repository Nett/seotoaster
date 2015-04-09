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

        $this->_view->activeLang = Tools_Localisation_Tools::getLangDefault();
        if (null !== ($userLang = $this->_request->getCookie('userLanguage', null))) {
            $this->_view->activeLang = $userLang;
        }

        return $this->_view->render('select.lang.phtml');
    }
}
