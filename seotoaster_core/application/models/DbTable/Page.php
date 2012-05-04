<?php

class Application_Model_DbTable_Page extends Zend_Db_Table_Abstract {

	protected $_name = 'page';

	protected $_referenceMap = array(
		'Template' => array(
			'columns'       => 'template_id',
			'refTableClass' => 'Application_Model_DbTable_Template'
		),
		'Silo'     => array(
			'columns'       => 'silo_id',
			'refTableClass' => 'Application_Model_DbTable_Silo'
		)
	);

	protected $_dependentTables = array(
		'Application_Model_DbTable_PageFeaturedarea',
		'Application_Model_DbTable_News',
		'Application_Model_DbTable_Optimized'
	);

    public function fetchAllMenu($menuType, $fetchSysPages = false) {
        $where     = $this->getAdapter()->quoteInto("show_in_menu = '?'", $menuType);
        $sysWhere  = $this->getAdapter()->quoteInto("system = '?'", intval($fetchSysPages));
        $where    .= (($where) ? ' AND ' . $sysWhere : $sysWhere);
        $select = $this->getAdapter()->select()
            ->from('page', array(
                'id',
                'navName' => 'nav_name',
                'h1',
                'url',
                'protected',
                'parentId' => 'parent_id'
            )
        )->joinLeft('optimized', 'page_id = id', array(
            'optimizedUrl' => 'url',
            'optimizedH1'  => 'h1',
            'optimizedNavName'    => 'nav_name'
        ))
        ->where($where)
        ->order(array('order'));
        return $this->getAdapter()->fetchAll($select);
    }

    /**
     * Find page and all data for this page from the optimized table
     *
     * @param integer $id
     * @return Zend_Db_Table_Row
     */
    public function findPage($id) {
        $where = $this->getAdapter()->quoteInto('id=?', $id);
        $select = $this->getAdapter()->select()
            ->from('page')
            ->joinLeft('optimized', 'page_id=id', array(
                'optimizedUrl'               => 'url',
                'optimizedH1'                => 'h1',
                'optimizedHeaderTitle'       => 'header_title',
                'optimizedNavName'           => 'nav_name',
                'optimizedTargetedKeyPhrase' => 'targeted_key_phrase',
                'optimizedMetaDescription'   => 'meta_keywords',
                'optimizedMetaKeywords'      => 'meta_keywords'
            ))
            ->where($where);
        return new Zend_Db_Table_Row(array(
            'table' => $this,
            'data'  => $this->getAdapter()->fetchRow($select)
        ));
    }

    public function fetchAllPages($where = '', $order = array()) {
        $select = $this->getAdapter()->select()
            ->from('page')
            ->joinLeft('optimized', 'page_id=id', array(
                'optimizedUrl'               => 'url',
                'optimizedH1'                => 'h1',
                'optimizedHeaderTitle'       => 'header_title',
                'optimizedNavName'           => 'nav_name',
                'optimizedTargetedKeyPhrase' => 'targeted_key_phrase',
                'optimizedMetaDescription'   => 'meta_keywords',
                'optimizedMetaKeywords'      => 'meta_keywords'
            ))
            ->where($where)
            ->order($order);
        $r = $this->getAdapter()->fetchAll($select);
        return new Zend_Db_Table_Rowset(array(
            'table' => $this,
            'rowClass' => $this->getRowClass(),
            'data'  => $this->getAdapter()->fetchAll($select)
        ));
    }
}

