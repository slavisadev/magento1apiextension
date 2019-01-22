<?php

class ApiExtension_Magento_Adminhtml_WebhooksController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var Mage_Adminhtml_Model_Session
     */
    private $session;

    /**
     * @var Mage_Adminhtml_Helper_Data
     */
    private $adminhtml;

    /**
     * Grid action
     */
    public function indexAction()
    {
//        $this->_title($this->__('Manager Webhooks'));
//        $this->_initAction();
//        $this->renderLayout();

        // instantiate the grid container
        $storeentityBlock = $this->getLayout()
            ->createBlock('apiExtension/adminhtml_webhooks');

        // Add the grid container as the only item on this page
        $this->loadLayout()
            ->_addContent($storeentityBlock)
            ->renderLayout();
    }

    /**
     * Edit action
     */
    public function editAction()
    {
        $this->init();
        $this->_title($this->__('Magento'));
        $this->_title($this->__('Webhooks'));
        $this->_title($this->__('Edit Item'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('apiExtension/webhooks')->load($id);

        if ($model->getId()) {
            Mage::register('webhooks_data', $model);
            $this->loadLayout();

            $this->_addBreadcrumb($this->adminhtml->__('Webhooks Manager'), $this->adminhtml->__('Webhooks Manager'));
            $this->_addBreadcrumb($this->adminhtml->__('Webhooks Description'), $this->adminhtml->__('Webhooks Description'));
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent(
                $this->getLayout()->createBlock('apiExtension/adminhtml_webhooks_edit')
            );

            $this->renderLayout();
        } else {
            $this->session->addError(Mage::helper('apiExtension')->__('Webhook does not exist.'));
            $this->_redirect("*/*/");
        }
    }

    /**
     * New action
     */
    public function newAction()
    {
        $this->init();
        $this->_title($this->__('Magento'));
        $this->_title($this->__('Webhooks'));
        $this->_title($this->__('New Item'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('apiExtension/webhooks')->load($id);

        $data = $this->session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('webhooks_data', $model);

        $this->loadLayout();
        $this->_setActiveMenu('apiExtension/webhooks');

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addBreadcrumb($this->adminhtml->__('Webhooks Manager'), $this->adminhtml->__('Webhooks Manager'));
        $this->_addBreadcrumb($this->adminhtml->__('Webhooks Description'), $this->adminhtml->__('Webhooks Description'));


        $this->_addContent(
            $this->getLayout()->createBlock('apiExtension/adminhtml_webhooks_edit')
        );

        $this->renderLayout();

    }

    /**
     * Save action
     *
     * @return $this|Mage_Core_Controller_Varien_Action
     */
    public function saveAction()
    {
        $this->init();
        $postData = $this->getRequest()->getPost();

        if ($postData) {

            try {
                if (filter_var($postData['url'], FILTER_VALIDATE_URL) === false) {
                    $this->session->addError($this->adminhtml->__('URL is not valid'));
                    return $this->_redirect("*/*/edit", array('id' => $this->getRequest()->getParam('id')));
                }

                $model = Mage::getModel('apiExtension/webhooks')
                    ->addData($postData)
                    ->setId($this->getRequest()->getParam('id'))
                    ->save();

                $this->session->addSuccess($this->adminhtml->__('Webhooks was successfully saved'));
                $this->session->setWebhooksData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect("*/*/edit", array('id' => $model->getId()));
                    return;
                }

                return $this->_redirect("*/*/");
            } catch (Exception $e) {
                $this->session->addError($e->getMessage());
                $this->session->setWebhooksData($this->getRequest()->getPost());

                return $this->_redirect("*/*/edit", array('id' => $this->getRequest()->getParam('id')));
            }
        }

        $this->_redirect("*/*/");
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        $this->init();
        $id = $this->getRequest()->getParam('id');

        if ($id > 0) {
            try {
                Mage::getModel('apiExtension/webhooks')->load($id)->delete();

                $this->session->addSuccess($this->adminhtml->__('Webhook was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                $this->session->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $id));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Init action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->loadLayout();

        return $this;
    }

    /**
     * Init global
     */
    private function init()
    {
        $this->session = Mage::getSingleton('adminhtml/session');
        $this->adminhtml = Mage::helper('adminhtml');
    }
}
