<?php

/**
 * Quokka Framework
 *
 * @copyright Copyright 2012 Fabien Casters
 * @license http://www.gnu.org/licenses/lgpl.html Lesser General Public License
 */

namespace Quokka\Mvc;

/**
 * \Quokka\Mvc\Application
 *
 * @package Quokka
 * @subpackage Mvc
 * @author Fabien Casters
 */
class Application {

    /**
     * @var string
     */
    protected $_namespace = '';

    /**
     * @var \Quokka\Network\Request
     */
    protected $_request = null;

    /**
     * @var \Quokka\Network\Response
     */
    protected $_response = null;

    /**
     * @var \Quokka\Routing\Router
     */
    protected $_router = null;

    /**
     * @var \Quokka\View\View
     */
    protected $_layout = null;

    /**
     * @var array
     */
    protected $_resources = [];

    /**
     * @var array
     */
    protected $_plugins = [];

    /**
     *
     * @return void
     */
    public function __construct( $namespace = 'Application' ) {

        $this->_namespace = $namespace;
        $this->_request = new \Quokka\Network\Request();
        $this->_response = new \Quokka\Network\Response();
        $this->_router = new Router();
    }

    /**
     *
     * @return void
     */
    public function run() {

        if( !$this->getRouter()->route( $this->getRequest() ) ) {

            $this->getRequest()->setParam('controller', 'error');
            $this->getRequest()->setParam('action', 'index');
        }

        $this->_preDispatch();
        do {

            $content = $this->_dispatch();

        } while( !$this->getRequest()->isDispatched() );

        if( $this->_layout !== null && $content !== false ) {

            $this->_layout->set('content', $content);
            $content = $this->_layout->render();
        }

        $this->getResponse()->setBody($content);

        $this->_postDispatch();

        $this->getResponse()->send();
    }

    /**
     *
     * @return void
     */
    private function _preDispatch() {

        foreach($this->_plugins as $plugin)
            $plugin->preDispatch();
    }

    /**
     *
     * @return void
     */
    private function _postDispatch() {

        foreach($this->_plugins as $plugin)
            $plugin->postDispatch();
    }

    /**
     *
     * @return string
     */
    private function _dispatch() {

        $request = $this->getRequest();
        $request->setDispatched(true);

        if($request->getParam('module', null) === null )
            $class = 'Application\\Controller\\' .
                     ucfirst($request->getParam('controller')) . 'Controller';
        else
            $class = 'Application\\Module\\' .
                     ucfirst($request->getParam('module')) . '\\' .
                     ucfirst($request->getParam('controller')) . 'Controller';

        $method = $request->getParam('action') . 'Action';

        $controller = new $class();
        $controller->setApplication($this);
        $controller->init();

        return $controller->$method();
    }

    /**
     *
     * @return \Quokka\Network\Response
     */
    public function getResponse() {

        return $this->_response;
    }

    /**
     *
     * @return \Quokka\Network\Request
     */
    public function getRequest() {

        return $this->_request;
    }

    /**
     *
     * @return \Quokka\Mvc\Router
     */
    public function getRouter() {

        return $this->_router;
    }

    /**
     *
     * @return \Quokka\Mvc\View\View
     */
    public function getLayout() {

        return $this->_layout;
    }

    /**
     *
     * @param $layout \Quokka\Mvc\View\View
     * @return void
     */
    public function setLayout( $layout ) {

        $this->_layout = $layout;
    }

    /**
     *
     * @param $plugin \Quokka\Mvc\AbstractPlugin
     * @return void
     */
    public function addPlugin($plugin) {

        $plugin->setApplication($this);
        $this->_plugins[] = $plugin;
    }

    /**
     *
     * @param $key string
     * @param $value mixed
     */
    public function addResource($key, $value) {

        $this->_resources[$key] = $value;
    }

    /**
     *
     * @param $key string
     * @return mixed
     */
    public function getResource($key) {

        return $this->_resources[$key];
    }
}
