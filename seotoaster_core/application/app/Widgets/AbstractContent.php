<?php
abstract class Widgets_AbstractContent extends Widgets_Abstract
{
    const OPTION_READONLY = 'readonly';
    const OPTION_HREF     = 'href';

    protected $_acl           = null;

    protected $_type          = null;

    protected $_pageId        = null;

    protected $_name          = null;

    protected $_lang          = null;

    protected $_defaultLangId = null;

    protected $_container     = null;

    protected function _init()
    {
        parent::_init();
        $this->_name   = $this->_options[0];
        $this->_pageId = $this->_toasterOptions['id'];
        $this->_lang   = $this->_toasterOptions['lang'];
        if ($this->_type == Application_Model_Models_Container::TYPE_STATICCONTENT
            || $this->_type == Application_Model_Models_Container::TYPE_STATICHEADER
            || $this->_type == Application_Model_Models_Container::TYPE_PREPOPSTATIC
        ) {
            $this->_pageId = 0;
        }

        $roleId = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        $this->_cacheId = 'page_'.$this->_pageId.'_lang_'.$this->_lang.'_'.$roleId.'_lifeTime_'.$this->_cacheLifeTime;
        $contentId      = $this->_name.'_'.$this->_type.'_pid_'.$this->_pageId;
        array_push($this->_cacheTags, preg_replace('/[^\w\d_]/', '', $contentId));
    }

    /**
     * Returns a link to add/edit content
     *
     * @return string
     */
    protected function _generateAdminControl($width = 0, $height = 0, $hint = '')
    {
        if (end($this->_options) == self::OPTION_READONLY || end($this->_options) == self::OPTION_HREF) {
            return false;
        }

        $controlIcon = 'editadd';
        if (in_array('static', $this->_options)) {
            $controlIcon .= '-static';
        }

        $widgetName   = explode('_', get_called_class());
        $widgetName   = strtolower(end($widgetName));
        $controlIcon .= '-'.$widgetName.'.png';
        if (!$hint) {
            $hint = 'edit '.($widgetName == 'content' ? '' : $widgetName).' content';
        }

        $containerId = ($this->_container !== null) ? $this->_container->getId() : null;
        $lang = isset($_COOKIE["localization"]) ? '/lang/' . $_COOKIE["localization"] : '';
        $url  = $this->_toasterOptions['websiteUrl'].'backend/backend_content/';
        if(null === $this->_container){
            $url .= 'add/containerName/'.$this->_name.'/pageId/'.$this->_toasterOptions['defaultLangId'];
        }else if($this->_container->getId() === null){
            $url .= 'edit/defaultLangId/'.$this->_container->getDefaultLangId().'/pageId/'.$this->_toasterOptions['id'] . $lang;
        }else{
            $url .= 'edit/id/'.$this->_container->getId() . $lang;
        }
        $url .= '/containerType/'.$this->_type;

        return '<a class="tpopup generator-links" data-pwidth="'.$width.'" data-pheight="'.$height.'" title="Click to '
            .$hint.'" href="javascript:;" data-url="'.$url.'"><img width="26" height="26" src="'
            .$this->_toasterOptions['websiteUrl'].'system/images/'.$controlIcon.'" alt="'.$hint.'" /></a>';
    }

    protected function _find()
    {
        if (!isset($this->_toasterOptions['containers'])) {
            return null;
        }

        $containerKey = md5(
            implode('-', array($this->_name, $this->_pageId === null ? 0 : $this->_pageId, $this->_type, $this->_lang))
        );
        if (!array_key_exists($containerKey, $this->_toasterOptions['containers'])
            && !array_key_exists($this->_name.'-'.$this->_type, $this->_toasterOptions['containersMain'])) {
            return null;
        }

        if (!array_key_exists($containerKey, $this->_toasterOptions['containers'])) {
            $container = $this->_toasterOptions['containersMain'][$this->_name.'-'.$this->_type];
            $container['id'] = null;
            $container['page_id'] = $this->_pageId;
            $widget = new Application_Model_Models_Container();
            return $widget->setName($this->_name)->setOptions($container);
        } else {
            $container = $this->_toasterOptions['containers'][$containerKey];
            if ((($container['page_id'] == $this->_pageId) || ($container['page_id'] === $this->_toasterOptions['defaultLangId']) || ($container['page_id'] === null))
                && $container['container_type'] == $this->_type
            ) {
                $widget = new Application_Model_Models_Container();

                return $widget->setName($this->_name)->setOptions($container);
            }
        }

        return null;
    }
}
