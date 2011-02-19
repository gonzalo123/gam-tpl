<?php
//localhost:8082/tests/tpl/tpl.php
class Tpl_Instance
{
    private $_tpl    = null;
    private $_conf   = null;
    private $_params = array();
    
    function __construct($tpl, $conf)
    {
        $this->_tpl  = $tpl;
        $this->_conf = $conf;
    }
    
    protected function clean($var)
    {
        if (is_scalar($var)) {
           return (string) $var;
        } elseif (is_array($var)) {
            return (array) $var;
        } else {
            throw new Exception('Template error');
        }
    }
    
    protected function _($var)
    {
        if (isset($this->_params[$var])) {
            return $this->clean($this->_params[$var]);
        } else {
            if ($this->_conf[Tpl::THROW_EXCEPTION_WITH_PARAMS] === true) {
                throw new Exception("Template error. Param '{$var}' not asigned");
            } else {
                return null;
            }
        }
    }
    
    public function addParam($key, $value)
    {
        $this->_params[$key] = $value;
    }
    
    public function render($params=array())
    {
        $this->_params = array_merge_recursive($this->_params, $params);
        
        if (is_null($this->_conf[Tpl::TPL_DIR])) {
            $_tplFile = $this->_tpl;
        } else {
            $_tplFile = $this->_conf[Tpl::TPL_DIR] . "/" . $this->_tpl;
        }

        if (!is_file($_tplFile)) {
            throw new Exception('Template file not found');
        }
        
        ini_set('implicit_flush',false);
        ob_start();
        include ($_tplFile);
        
        $out = ob_get_contents();
        ob_end_clean();
        ini_set('implicit_flush',true);
        
        return $out;
    }
}

class Tpl 
{ 
    private static $_instance = null;
    /**
     * @return Tpl
     */
    public static function singleton()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = self::factory();
        }
        return self::$_instance;
    }
    
    /**
     * @return Tpl
     */
    public static function factory()
    {
        return new self;
    }

    /**
     * @param string $tpl
     * @param array $conf
     * @return Tpl_Instance
     */
    public function init($tpl)
    {
        return new Tpl_Instance($tpl, $this->_conf);
    }
    
    const TPL_DIR = 0;
    const THROW_EXCEPTION_WITH_PARAMS = 1;
    
    private $_conf = array(
        Tpl::TPL_DIR => null,
        Tpl::THROW_EXCEPTION_WITH_PARAMS => true
        );
    
    function setConf($key, $value)
    {
        $this->_conf[$key] = $value;
    }
}

// Sets the path of templates. If nuls asumes file is absolute
Tpl::singleton()->setConf(Tpl::TPL_DIR, realpath(dirname(__FILE__)));
echo Tpl::singleton()->init('demo1.phtml')->render(array(
    'var1' => 1,
    'var2' => 2,
    'var3' => array(1, 2, 3, 4, 5, 6, 7)
    ));
    
// The same instance a different template and params added in a different way
$tpl = Tpl::singleton()->init('demo2.phtml');
$tpl->addParam('header', 'header');
$tpl->addParam('footer', 'footer');
echo $tpl->render();


// Disable exceptions if we don't assign a variable
Tpl::singleton()->setConf(Tpl::THROW_EXCEPTION_WITH_PARAMS, false);
$tpl = Tpl::singleton()->init('demo1.phtml');
$tpl->addParam('var1', 'aaaa');
$tpl->addParam('var3', array(1, 2, 3, 4, 5, 6, 7));
echo $tpl->render();

// Using factory
$objTpl = Tpl::factory();
$objTpl->setConf(Tpl::THROW_EXCEPTION_WITH_PARAMS, true);
try {
    $tpl = $objTpl->init('demo1.phtml');
    $tpl->addParam('var1', 'aaaa');
    $tpl->addParam('var3', array(1, 2, 3, 4, 5, 6, 7));
    echo $tpl->render();
} catch (Exception $e) {
    echo "<p>" . $e->getMessage() . "</p>";
}
