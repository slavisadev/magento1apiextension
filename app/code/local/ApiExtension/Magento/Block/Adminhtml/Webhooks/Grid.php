<?php

class ApiExtension_Magento_Block_Adminhtml_Webhooks_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @var ApiExtension_Magento_Helper_Data
     */
    private $helper;

    /**
     * ApiExtension_Magento_Block_Adminhtml_Webhooks_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('webhooks-grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);

        $this->helper = Mage::helper('apiExtension');
    }

    /**
     * @return array
     */
    public static function getOptionsForActive()
    {
        return array(
            0 => 'No',
            1 => 'Yes',
        );
    }

    /**
     * @return array
     */
    public static function getAvailableHooks()
    {
        $result = array();
        $codes = array_keys(Mage::helper('apiExtension')->getAvailableHooks());

        foreach ($codes as $code) {
            $result[$code] = $code;
        }

        return $result;
    }

    /**
     * @param $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl("*/*/edit", array('id' => $row->getId()));
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getModel('apiExtension/webhooks')->getCollection());

        return parent::_prepareCollection();
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => $this->helper->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'id',
        ));

        $this->addColumn('code', array(
            'header' => $this->helper->__('Code'),
            'index' => 'code',
            'type' => 'options',
            'options' => self::getAvailableHooks(),
        ));

        $this->addColumn('url', array(
            'header' => $this->helper->__('URL'),
            'index' => 'url',
        ));

        $this->addColumn('description', array(
            'header' => $this->helper->__('Description'),
            'index' => 'description',
        ));

        $this->addColumn('data', array(
            'header' => $this->helper->__('Data'),
            'index' => 'data',
        ));

        $this->addColumn('token', array(
            'header' => $this->helper->__('Token'),
            'index' => 'token',
        ));

        $this->addColumn('active', array(
            'header' => $this->helper->__('Active'),
            'index' => 'active',
            'type' => 'options',
            'options' => self::getOptionsForActive()
        ));

        $this->addColumn('action_edit', array(
            'header' => $this->helper->__('Action'),
            'width' => 15,
            'sortable' => false,
            'filter' => false,
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => $this->helper->__('Edit'),
                    'url' => array('base' => "*/*/edit"),
                    'field' => 'id',
                ),
            )
        ));

        return parent::_prepareColumns();
    }
}
