<?php
/**
 * Prepop widget
 *
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 5/24/12
 * Time: 3:58 PM
 */
class Widgets_Prepop_Prepop extends Widgets_AbstractContent {

    protected $_prepopName        = '';

    protected $_prepopContent     = null;

    protected $_prepopContainerId = null;

    protected function _init() {
        parent::_init();
        $this->_prepopName          = array_shift($this->_options);
        $this->_view                = new Zend_View(array(
            'scriptPath' => __DIR__ . '/views'
        ));
        $this->_view->prepopName    = $this->_prepopName;
        $this->_view->commonOptions = array(
            'pageId'        => $this->_toasterOptions['id'],
            'containerType' => Application_Model_Models_Container::TYPE_PREPOP,
            'containerName' => $this->_prepopName
        );
    }

    protected function  _load() {
        if(!isset($this->_options[0])) {
            throw new Exceptions_SeotoasterWidgetException('Not enough parameters for the widget <strong>prepop</strong>.');
        }

        $prepop = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_prepopName, $this->_toasterOptions['id'], Application_Model_Models_Container::TYPE_PREPOP);
        if($prepop) {
            $this->_prepopContent = $prepop->getContent();
            $this->_prepopContainerId = $prepop->getId();
        }

        // user role should be a member at least to be able to edit
        if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
            return $this->_prepopContent;
        }

        //assign common view vars for the prepop
        $this->_view->prepopContent    = $this->_prepopContent;
        $this->_view->prepopConainerId = $this->_prepopContainerId;

        $rendererName = '_renderPrepop' . ucfirst(array_shift($this->_options));
        if(method_exists($this, $rendererName)) {
            return $this->$rendererName();
        }
        throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong prepop type <strong>' . $prepopType . '</strong>'));

    }

    protected function _renderPrepopSelect() {
        $arrayValues = array_map(function($value) {
            return trim($value);
        }, array_values($this->_options));
        $this->_view->options = array_combine($arrayValues, array_map(function($option) {
            return ucfirst($option);
        }, $arrayValues));
        return $this->_view->render('select.prepop.phtml');
    }

    protected function _getAllowedOptions() {

    }

}
