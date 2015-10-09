<?php
/**
 * Class Application_Model_Mappers_PageMapper
 * @method static Application_Model_Mappers_PageMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_Page getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_PageMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_Page';

    protected $_model = 'Application_Model_Models_Page';

    protected $_optimized = false;

    protected $_originalsOnly = false;

    protected $_optimizedFields = array(
        'h1',
        'header_title',
        'url',
        'nav_name',
        'meta_description',
        'meta_keywords',
        'targeted_key_phrase',
        'teaser_text'
    );

    /**
     * @param Application_Model_Models_Page $page
     * @return mixed
     * @throws Exceptions_SeotoasterException
     */
    public function save($page)
    {
        if (!$page instanceof Application_Model_Models_Page) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Page instance');
        }
        $data = array(
            'template_id'         => $page->getTemplateId(),
            'parent_id'           => $page->getParentId(),
            'h1'                  => $page->getH1(),
            'header_title'        => $page->getHeaderTitle(),
            'url'                 => $page->getUrl(),
            'default_lang_id'     => $page->getDefaultLangId(),
            'lang'                => $page->getLang(),
            'nav_name'            => $page->getNavName(),
            'meta_description'    => $page->getMetaDescription(),
            'meta_keywords'       => $page->getMetaKeywords(),
            'teaser_text'         => $page->getTeaserText(),
            'show_in_menu'        => $page->getShowInMenu(),
            'order'               => $page->getOrder(),
            'silo_id'             => $page->getSiloId(),
            'targeted_key_phrase' => $page->getTargetedKeyPhrase(),
            'system'              => intval($page->getSystem()),
            'draft'               => intval($page->getDraft()),
            'news'                => intval($page->getNews()),
            'publish_at'          => (!$page->getPublishAt()) ? null : date('Y-m-d', strtotime($page->getPublishAt())),
            'preview_image'       => $page->getPreviewImage(),
            'external_link_status' => $page->getExternalLinkStatus(),
            'external_link'       => $page->getExternalLink()
        );


        if ($page->getId()) {
            $this->getDbTable()->update($data, array('id = ?' => $page->getId()));

            $this->getDbTable()->update(
                array(
                    'draft'               => intval($page->getDraft()),
                    'publish_at'          => (!$page->getPublishAt()) ? null : date('Y-m-d', strtotime($page->getPublishAt()))
                ),
                array('parent_id = ?' => $page->getId())
            );
        } else {
            $pageId = $this->getDbTable()->insert($data);
            $page->setId($pageId);

            if ((int)$page->getDefaultLangId() === 0) {
                $page->setDefaultLangId($pageId);
                return $this->save($page);
            }
        }

        //save page options
        $options = $page->getExtraOptions();
        $pageHasOptionTable = new Application_Model_DbTable_PageHasOption();
        if (!empty($options)) {
            $pageHasOptionTable->getAdapter()->beginTransaction();
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
            foreach ($options as $option) {
                $pageHasOptionTable->insert(
                    array(
                        'page_id'   => $page->getId(),
                        'option_id' => $option
                    )
                );
            }
            $pageHasOptionTable->getAdapter()->commit();
        } else {
            $pageHasOptionTable->delete($pageHasOptionTable->getAdapter()->quoteInto('page_id = ?', $page->getId()));
        }

        return $page;
    }

    public function fetchAll($where = '', $order = array(), $fetchSysPages = false, $originalsOnly = false)
    {
        $dbTable = $this->getDbTable();

        //exclude system pages from select
        $sysWhere = $dbTable->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
        $order[] = 'order';
        $entries = array();
        $resultSet = $dbTable->fetchAllPages($where, $order, $originalsOnly);

        if (null === $resultSet) {
            return null;
        }

        $this->_originalsOnly = $originalsOnly;
        if (!$resultSet || empty($resultSet)) {
            return null;
        }

        /*foreach($resultSet as $row) {
            $row       = array_merge(array('extraOptions' => $this->getDbTable()->fetchPageOptions($row->id)), $row->toArray());
            $entries[] = $this->_toModel($row, $originalsOnly);
        }*/

        $model = $this->_model;
        $entries = array_map(
            function ($row) use (&$dbTable, $model, &$originalsOnly) {
                $row = array_merge($row, array('extraOptions' => $dbTable->fetchPageOptions($row['id'])));
                return new $model($row);
            },
            $resultSet->toArray()
        );

        return $entries;
    }

    /**
     * Fetch pages by given option
     *
     * @param string $option
     * @param bool   $firstOccurrenceOnly If true returns only first element of the result array
     * @return array|null
     */
    public function fetchByOption($option, $firstOccurrenceOnly = false)
    {
        $entries = array();
        $optionTable = new Application_Model_DbTable_PageOption();
        $optionRowset = $optionTable->find($option);
        if (!$optionRowset) {
            return null;
        }
        $optionRow = $optionRowset->current();
        if (!$optionRow) {
            return null;
        }
        $pagesRowset = $optionRow->findManyToManyRowset(
            'Application_Model_DbTable_Page',
            'Application_Model_DbTable_PageHasOption'
        );
        foreach ($pagesRowset as $pageRow) {
            $templateRow = $pageRow->findParentRow('Application_Model_DbTable_Template');
            $pageRow = $pageRow->toArray();
            $pageRow['content'] = ($templateRow !== null) ? $templateRow->content : '';
            $pageRow['extraOptions'] = Application_Model_Mappers_PageMapper::getInstance()->getDbTable(
            )->fetchPageOptions($pageRow['id']);
            $select = $this->getDbTable()->getAdapter()->select()->from(
                'container',
                array(
                    'uniqHash' => new Zend_Db_Expr("MD5(CONCAT_WS('-',`name`, COALESCE(`page_id`, 0), `container_type`, `lang`))"),
                    'id',
                    'name',
                    'page_id',
                    'container_type',
                    'content',
                    'published',
                    'publishing_date',
                    'default_lang_id'
                )
            )->where('page_id IS NULL OR page_id = ?', $pageRow['id']);
            $pageRow['containers'] = $this->getDbTable()->getAdapter()->fetchAssoc($select);
            if($pageRow['default_lang_id'] !== $pageRow['id']){
                $selectFrom = $this->getDbTable()->getAdapter()->select()->from('container', array(
                    'uniqName' => new Zend_Db_Expr("CONCAT_WS('-', `name`,`container_type`)"),
                    'id',
                    'name',
                    'page_id',
                    'container_type',
                    'content',
                    'published',
                    'publishing_date',
                    'default_lang_id'
                ));
                $pageMainLang = Zend_Locale::getLocaleToTerritory(Tools_Localization_Tools::getLangDefault());
                $selectMain = $selectFrom->where("page_id = '".$pageRow['default_lang_id']."' AND lang = '".$pageMainLang."'")
                    ->orWhere("page_id IS NULL AND lang = '".$pageMainLang."'");
                $pageRow['containersMain'] = $this->getDbTable()->getAdapter()->fetchAssoc($selectMain);
            }
            $lang  = Zend_Locale::getLocaleToTerritory(isset($_COOKIE["localization"]) ? $_COOKIE["localization"] : Tools_Localization_Tools::getLangDefault());
            if($pageRow['lang'] === $lang) {
                $entries[] = $this->_toModel($pageRow);
            }
        }
        if ($firstOccurrenceOnly) {
            return (isset($entries[0])) ? $entries[0] : null;
        }
        return $entries;
    }

    public function fetchAllUrls()
    {
        $select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
            ->from($this->getDbTable()->info('name'), array('url'));
        return $this->getDbTable()->getAdapter()->fetchCol($select);
    }

    public function fetchAllStaticMenuPages($lang = null)
    {
        $lang = ($lang !== null) ? $lang : Zend_Locale::getLocaleToTerritory(Tools_Localization_Tools::getLangDefault());
        $where = $this->getDbTable()->getAdapter()->quoteInto(
            "show_in_menu = '?'",
            Application_Model_Models_Page::IN_STATICMENU
        );
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("lang = ?", $lang);
        return $this->fetchAll($where);
    }

    public function fetchAllMainMenuPages()
    {
        return $this->getDbTable()->fetchAllMenu(Application_Model_Models_Page::IN_MAINMENU);
    }

    public function fetchAllDraftPages()
    {
        $lang = Tools_Localization_Tools::getLangDefault();
        $lang = Zend_Locale::getLocaleToTerritory($lang);
        return $this->fetchAll("draft = '1' AND lang = '".$lang."'", array(), true);
    }

    public function getDraftPagesCount()
    {
        $lang = Tools_Localization_Tools::getLangDefault();
        $lang = Zend_Locale::getLocaleToTerritory($lang);
        $table = $this->getDbTable();
        $select = $table->select()
            ->from($table, array('count' => new Zend_Db_Expr('COUNT(draft)')))
            ->where('draft = ?', '1')
            ->where('lang = ?', $lang)
            ->where('system = ?', '1');

        return $table->getAdapter()->fetchOne($select);
    }

    public function fetchAllNomenuPages($lang = null)
    {
        $lang = ($lang !== null) ? $lang : Zend_Locale::getLocaleToTerritory(Tools_Localization_Tools::getLangDefault());
        $where = sprintf(
            "show_in_menu = '%s' AND parent_id = %d AND news != '%s' AND lang = '%s'",
            Application_Model_Models_Page::IN_NOMENU,
            Application_Model_Models_Page::IDCATEGORY_DEFAULT,
            Application_Model_Models_Page::IS_NEWS_PAGE,
            $lang
        );
        return $this->fetchAll($where);
    }

    /**
     * @param  string $pageUrl
     * @return mixed
     */
    public function findByUrl($pageUrl, $pageLang = null)
    {
        if (!$pageUrl) {
            $pageUrl = Helpers_Action_Website::DEFAULT_PAGE;
        }
        if (!$pageLang) {
            $pageLang = Tools_Localization_Tools::getLangDefault();
        }
        $pageLang = Zend_Locale::getLocaleToTerritory($pageLang);
        $entry = $this->getDbTable()->findByUrl($pageUrl, $pageLang);

        if (!$entry) {
            return null;
        }

        $entry = array_merge($entry, array('extraOptions' => $this->getDbTable()->fetchPageOptions($entry['id'])));

        return new $this->_model($entry);
    }

    public function findErrorLoginLanding()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_ERRLAND, true);
    }

    public function findMemberLanding()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_MEMLAND, true);
    }

    public function findSignupLandign()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_SIGNUPLAND, true);
    }

    public function findByNavName($navName)
    {
        $lang  = Zend_Locale::getLocaleToTerritory(isset($_COOKIE["localization"]) ? $_COOKIE["localization"] : Tools_Localization_Tools::getLangDefault());
        $where = $this->getDbTable()->getAdapter()->quoteInto('nav_name = ?', $navName);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('lang = ?', $lang);
        return $this->_findWhere($where);
    }

    public function findByParentId($parentId, $draft = false)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', $parentId);
        if ($draft) {
            $where .= ' OR ' . $this->getDbTable()->getAdapter()->quoteInto('draft = ?', '1');
        }
        return $this->fetchAll($where);
    }

    public function fetchMainCategories()
    {
        $lang  = Zend_Locale::getLocaleToTerritory(isset($_COOKIE["localization"]) ? $_COOKIE["localization"] : Tools_Localization_Tools::getLangDefault());
        $where = $this->getDbTable()->getAdapter()->quoteInto('parent_id = ?', '0');
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('draft = ?', '0');
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('show_in_menu = ?', '1');
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('lang = ?', $lang);

        return $this->fetchAll($where);
    }


    public function selectCategoriesIdName($useNavName = false)
    {
        $result = array();
        $categories = $this->findByParentId(0, true);
        $translator = Zend_Registry::get('Zend_Translate');
        if (empty($categories)) {
            return array();
        }
        foreach ($categories as $key => $category) {
            if ($useNavName) {
                $categoryName = ($category->getProtected()) ? ($category->getNavName() . '*') : $category->getNavName();
            } else {
                $categoryName = ($category->getProtected()) ? ($category->getH1() . '*') : $category->getH1();
            }
            if ($category->draft == 1) {
                $categoryName .= ' ('.$translator->translate('draft').')';
            }
            $result[$category->getId()] = $categoryName;
        }
        return $result;
    }

    public function find404Page()
    {
        return $this->fetchByOption(Application_Model_Models_Page::OPT_404PAGE, true);
    }

    public function delete(Application_Model_Models_Page $page)
    {
        $where  = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $page->getId());
        if ($page->getId() == $page->getDefaultLangId()) {
            $where .= ' OR '.$this->getDbTable()->getAdapter()
                    ->quoteInto('default_lang_id = ?', $page->getDefaultLangId());
        }

        $deleteResult = $this->getDbTable()->delete($where);
        $page->notifyObservers();

        return $deleteResult;
    }

    public function fetchIdUrlPairs($lang)
    {
        $select = $this->getDbTable()->select(Zend_Db_Table::SELECT_WITHOUT_FROM_PART)
                ->from($this->getDbTable()->info('name'), array('id', 'url'))
                ->where('lang = ?', $lang)
                ->order('url');

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    protected function  _findWhere($where, $fetchSysPages = false)
    {
        $whereExploded = explode('=', $where);
        $spot = strpos($whereExploded[0], '.');
        if ($spot === false) {
            $whereExploded[0] = str_replace(substr($whereExploded[0], 0, $spot), '', $whereExploded[0]);
        }
        $where = implode('=', $whereExploded);
        $where = '(page.' . $where . ' OR optimized.' . $where . ')';

        $sysWhere = $this->getDbTable()->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where .= (($where) ? ' AND ' . $sysWhere : $sysWhere);

        $row = $this->getDbTable()->fetchAllPages($where);

        if (!$row) {
            return null;
        }

        if ($row instanceof Zend_Db_Table_Rowset) {
            $row = $row->current();
        }

        $rowTemplate = $row->findParentRow('Application_Model_DbTable_Template');
        $row = $row->toArray();
        $row['content'] = ($rowTemplate !== null) ? $rowTemplate->content : '';

        //set an extra options for the page
        $row['extraOptions'] = $this->getDbTable()->fetchPageOptions($row['id']);

        unset($rowTemplate);
        return $this->_toModel($row);
    }

    public function find($id, $originalsOnly = false)
    {
        if (!is_array($id)) {
            return $this->_findPage($id, $originalsOnly);
        }
        $pages = array();
        foreach ($id as $pageId) {
            if (null !== ($page = $this->_findPage($pageId, $originalsOnly))) {
                $pages[] = $page;
            }
        }
        return $pages;
    }

    public function fetchAllByContent($content, $originalsOnly = false)
    {
        $pages = $this->getDbTable()->fetchAllByContent($content, $originalsOnly);
        if (!$pages || empty($pages)) {
            return null;
        }
        return array_map(
            function ($pageData) {
                return new Application_Model_Models_Page($pageData);
            },
            $pages
        );
    }

    protected function _findPage($id, $originalsOnly)
    {
        $row = $this->getDbTable()->findPage(intval($id), $originalsOnly);
        if (null == $row) {
            return null;
        }
        return $this->_toModel($row, $originalsOnly);
    }

    protected function _toModel($row, $originalsOnly = false)
    {
        if ($row instanceof Zend_Db_Table_Row) {
            $row = $row->toArray();
        }
        return new $this->_model($row);
    }

    private function _isOptimized($row)
    {
        if ($row instanceof Zend_Db_Table_Row) {
            $row = $row->toArray();
        }
        $isOptimized = false;
        foreach ($row as $key => $value) {
            if (false !== (strpos($key, 'optimized', 0))) {
                $isOptimized = $isOptimized || (boolean)$value;
            }
        }
        return $isOptimized;
    }

    public function getPagesForSearchIndex($limit = null, $offset = null)
    {
        $select = $this->getDbTable()->getAdapter()->select()
                ->from(array('p' => 'page'), null)
                ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', null)
                ->joinLeft(array('c' => 'container'), 'p.id = c.page_id AND c.container_type = 1', null)
                ->columns(
                    array(
                        'id'              => 'p.id',
                        'previewImage'    => 'p.preview_image',
                        'url'             => new Zend_Db_Expr('COALESCE(o.url, p.url)'),
                        'h1'              => new Zend_Db_Expr('COALESCE(o.h1, p.h1)'),
                        'navName'         => new Zend_Db_Expr('COALESCE(o.nav_name, p.nav_name)'),
                        'headerTitle'     => new Zend_Db_Expr('COALESCE(o.header_title, p.header_title)'),
                        'metaKeywords'    => new Zend_Db_Expr('COALESCE(o.meta_keywords, p.meta_keywords)'),
                        'metaDescription' => new Zend_Db_Expr('COALESCE(o.meta_description, p.meta_description)'),
                        'teaserText'      => new Zend_Db_Expr('COALESCE(o.teaser_text, p.teaser_text)'),
                        'content'         => new Zend_Db_Expr('GROUP_CONCAT(c.content)')
                    )
                )
                ->where("p.system = '?'", 0)
                ->group('p.id');

        if (!is_null($offset) && is_numeric($offset)) {
            $offset = intval($offset);
        }

        if (!is_null($limit) && is_numeric($limit)) {
            $select->limit(intval($limit), $offset);
        }

        return $this->getDbTable()->getAdapter()->fetchAll($select);

    }

    public function isDraftCategory($id)
    {
        $query = $this->getDbTable()->getAdapter()->select()->from($this->getDbTable()->info('name'),
            array('draft'))->where('id =?', $id);
        $col = $this->getDbTable()->getAdapter()->fetchCol($query);
        if (current($col) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function publishChildPages($parentId)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto("parent_id =?", $parentId);
        return $this->getDbTable()->update(array('draft'=>0, 'system'=>0), $where);
    }

    public function updateLangPages($defaultLangId, $newData)
    {
        $dataUpdate = array(
            'template_id'          => $newData->getTemplateId(),
            'show_in_menu'         => $newData->getShowInMenu(),
            'order'                => $newData->getOrder(),
            'silo_id'              => $newData->getSiloId(),
            'targeted_key_phrase'  => $newData->getTargetedKeyPhrase(),
            'system'               => intval($newData->getSystem()),
            'draft'                => intval($newData->getDraft()),
            'news'                 => intval($newData->getNews()),
            'publish_at'           => (!$newData->getPublishAt()) ? null : date('Y-m-d', strtotime($newData->getPublishAt())),
            'external_link_status' => $newData->getExternalLinkStatus(),
            'external_link'        => $newData->getExternalLink()
        );
        if($newData->getParentId() <= 0){
            $dataUpdate['parent_id'] = $newData->getParentId();
        }
        $where = $this->getDbTable()->getAdapter()->quoteInto("default_lang_id =?", $defaultLangId);
        return $this->getDbTable()->update($dataUpdate, $where);
    }

    public function updateParentIdPages($id, $newParentId)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto("id =?", $id);
        return $this->getDbTable()->update(array('parent_id'=>$newParentId), $where);
    }

    public function updatePagesByDefaultId($defaultLangId, $updateArray)
    {
        if(!is_array($updateArray)){
            return null;
        }
        $where = $this->getDbTable()->getAdapter()->quoteInto("default_lang_id =?", $defaultLangId);
        return $this->getDbTable()->update($updateArray, $where);
    }

    public function getCurrentPageLocalData($defaultLangId)
    {
        $query = $this->getDbTable()->getAdapter()->select()
            ->from($this->getDbTable()->info('name'), array('lang', 'id', 'url'))
            ->where('default_lang_id = ?', $defaultLangId);

        return $this->getDbTable()->getAdapter()->fetchAssoc($query);
    }
}
