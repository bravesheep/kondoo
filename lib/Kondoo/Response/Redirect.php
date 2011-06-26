<?php
namespace Kondoo\Response;

use \Kondoo\Request;

class Redirect {
    /**
     * The redirected module.
     * @var string
     */
    private $module;
    
    /**
     * The redirected controller.
     * @var string
     */
    private $controller;
    
    /**
     * The redirected action.
     * @var string
     */
    private $action;
    
    /**
     * The params to use for the redirected action.
     * @var mixed
     */
    private $params;
    
    /**
     * The request this redirect originated from.
     * @var \Kondoo\Request
     */
    private $request;
    
    public function __construct($location, $params = null)
    {
        $parts = explode('/', $location);
        switch(count($parts)) {
            case 1:
                $this->module = null;
                $this->controller = null;
                $this->action = $parts[0];
                break;
            case 2:
                $this->module = null;
                $this->controller = $parts[0];
                $this->action = $parts[1];
                break;
            case 3:
                $this->module = $parts[0];
                $this->controller = $parts[1];
                $this->action = $parts[2];
                break;
            default:
                throw new Exception(sprintf(_("'%s' is not a valid action."), $location));
        }
        $this->params = $params;
        $this->request = null;
    }
    
    public function getModule()
    {
        if(is_null($this->module) && !is_null($this->request)) {
            return $this->request->getModule();
        }
        return $this->module;
    }
    
    public function getController()
    {
        if(is_null($this->controller) && !is_null($this->request)) {
            return $this->request->getController();
        }
        return $this->controller;
    }
    
    public function getAction()
    {
        if(is_null($this->action) && !is_null($this->request)) {
            return $this->request->getAction();
        }
        return $this->action;
    }
    
    public function getParams()
    {
        if(is_null($this->params) && !is_null($this->request)) {
            return $this->request->params();
        }
        return $this->params;
    }
    
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}