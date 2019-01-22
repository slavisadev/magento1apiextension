<?php

class ApiExtension_Magento_Model_Api2_Dispatcher extends Mage_Api2_Model_Dispatcher
{
    /**
     * Instantiate resource class, set parameters to the instance, run resource internal dispatch method
     *
     * @param Mage_Api2_Model_Request  $request
     * @param Mage_Api2_Model_Response $response
     *
     * @return Mage_Api2_Model_Dispatcher
     * @throws Mage_Api2_Exception
     */
    public function dispatch(Mage_Api2_Model_Request $request, Mage_Api2_Model_Response $response)
    {
        if (!$request->getModel() || !$request->getApiType()) {
            throw new Mage_Api2_Exception(
                'Request does not contain all necessary data', Mage_Api2_Model_Server::HTTP_BAD_REQUEST
            );
        }
        $model = self::loadResourceModel(
            $request->getModel(),
            $request->getApiType(),
            $this->getApiUser()->getType(),
            $this->getVersion($request->getResourceType(), $request->getVersion())
        );

        $model->setRequest($request);
        $model->setResponse($response);
        $model->setApiUser($this->getApiUser());
        $model->dispatch();

        $this->setNoRedirectHeaders($request, $model);

        return $this;
    }

    /**
     * Some Magento servers doest't returns 404, in order to prevent
     * this behavior, noRedirect param is sent
     *
     * @param Mage_Api2_Model_Request $request
     * @param                         $model
     */
    private function setNoRedirectHeaders(Mage_Api2_Model_Request $request, &$model)
    {
        if ($request->getParam('noRedirect')) {
            $location = null;
            $headers = $model->getResponse()->getHeaders();

            foreach ($headers as $index => $header) {
                if ($header['name'] === 'Location') {
                    $location = $headers[$index]['value'];
                    break;
                }
            }

            if ($location) {
                Mage::helper('apiExtension')->log($location);

                $model->getResponse()->setHeader('LocationNoRedirect', $location);
                $model->getResponse()->setHeader('Location', null, true);
            }
        }
    }
}
