<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to version 1.0 of the Zend Framework
 * license, that is bundled with this package in the file LICENSE.txt, and
 * is available through the world-wide-web at the following URL:
 * http://framework.zend.com/license/new-bsd. If you did not receive
 * a copy of the Zend Framework license and are unable to obtain it
 * through the world-wide-web, please send a note to license@zend.com
 * so we can mail you a copy immediately.
 *
 * @package    Zend_View
 * @subpackage Helpers
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Placeholder.php 7078 2007-12-11 14:29:33Z matthew $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Helper for setting and retrieving stylesheets
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helpers
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_HeadStyle extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_HeadStyle';

    /**
     * Allowed optional attributes
     * @var array
     */
    protected $_optionalAttributes = array('lang', 'title', 'media', 'dir');

    /**
     * Allowed media types
     * @var array
     */
    protected $_mediaTypes = array(
        'all', 'aural', 'braille', 'handheld', 'print', 
        'projection', 'screen', 'tty', 'tv'
    );

    /**
     * Capture type and/or attributes (used for hinting during capture)
     * @var string
     */
    protected $_captureAttrs = null;

    /**
     * Constructor
     *
     * Set separator to PHP_EOL.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSeparator(PHP_EOL);
    }
    
    /**
     * Return headStyle object
     *
     * Returns headStyle helper object; optionally, allows specifying 
     *
     * @param  string $content Stylesheet contents
     * @param  string $placement Append, prepend, or set
     * @param  string|array $attributes Optional attributes to utilize
     * @return Zend_View_Helper_HeadStyle
     */
    public function headStyle($content = null, $placement = 'APPEND', $attributes = array())
    {
        if ((null !== $content) && is_string($content)) {
            switch (strtoupper($placement)) {
                case 'SET':
                    $action = 'setStyle';
                    break;
                case 'PREPEND':
                    $action = 'prependStyle';
                    break;
                case 'APPEND':
                default:
                    $action = 'appendStyle';
                    break;
            }
            $this->$action($content, $attributes);
        }

        return $this;
    }

    /**
     * Overload method calls
     *
     * Allows the following method calls:
     * - appendStyle($content, $attributes = array())
     * - offsetSetStyle($index, $content, $attributes = array())
     * - prependStyle($content, $attributes = array())
     * - setStyle($content, $attributes = array())
     * 
     * @param  string $method 
     * @param  array $args 
     * @return void
     * @throws Zend_View_Exception When no $content provided or invalid method
     */
    public function __call($method, $args)
    {
        if (preg_match('/^(?P<action>set|(ap|pre)pend|offsetSet)(Style)$/', $method, $matches)) {
            $index  = null;
            $argc   = count($args);
            $action = $matches['action'];

            if ('offsetSet' == $action) {
                if (0 < $argc) {
                    $index = array_shift($args);
                    --$argc;
                }
            }

            if (1 > $argc) {
                require_once 'Zend/View/Exception.php';
                throw new Zend_View_Exception(sprintf('Method "%s" requires minimally content for the stylesheet', $method));
            }

            $content = $args[0];
            $attrs   = array();
            if (isset($args[1])) {
                $attrs = (array) $args[1];
            }

            $item = $this->createData($content, $attrs);

            if ('offsetSet' == $action) {
                $this->offsetSet($index, $item);
            } else {
                $this->$action($item);
            }

            return $this;
        }

        require_once 'Zend/View/Exception.php';
        throw new Zend_View_Exception(sprintf('Invalid method %s::%s()', __CLASS__, $method));
    }

    /**
     * Determine if a value is a valid style tag
     * 
     * @param  mixed $value 
     * @param  string $method 
     * @return boolean
     */
    protected function _isValid($value)
    {
        if ((!$value instanceof stdClass)
            || !isset($value->content)
            || !isset($value->attributes))
        {
            return false;
        }

        return true;
    }

    /**
     * Override append to enforce style creation
     * 
     * @param  mixed $value 
     * @return void
     */
    public function append($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('Invalid value passed to append; please use appendStyle()');
        }

        return parent::append($value);
    }
   
    /**
     * Override offsetSet to enforce style creation
     * 
     * @param  string|int $index
     * @param  mixed $value 
     * @return void
     */
    public function offsetSet($index, $value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('Invalid value passed to offsetSet; please use offsetSetStyle()');
        }

        return parent::offsetSet($index, $value);
    }

    /**
     * Override prepend to enforce style creation
     * 
     * @param  mixed $value 
     * @return void
     */
    public function prepend($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('Invalid value passed to prepend; please use prependStyle()');
        }

        return parent::prepend($value);
    }

    /**
     * Override set to enforce style creation
     * 
     * @param  mixed $value 
     * @return void
     */
    public function set($value)
    {
        if (!$this->_isValid($value)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('Invalid value passed to set; please use setStyle()');
        }

        return parent::set($value);
    }

    /**
     * Start capture action
     * 
     * @param  mixed $captureType 
     * @param  string $typeOrAttrs 
     * @return void
     */
    public function captureStart($type = Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $attrs = null)
    {
        $this->_captureAttrs = $attrs;
        return parent::captureStart($type);
    }
    
    /**
     * End capture action and store
     * 
     * @return void
     */
    public function captureEnd()
    {
        $content             = ob_get_clean();
        $attrs               = $this->_captureAttrs;
        $this->_captureAttrs = null;
        $this->_captureLock  = false;

        switch ($this->_captureType) {
            case self::SET:
                $this->setStyle($content, $attrs);
                break;
            case self::PREPEND:
                $this->prependStyle($content, $attrs);
                break;
            case self::APPEND:
            default:
                $this->appendStyle($content, $attrs);
                break;
        }
    }

    /**
     * Convert content and attributes into valid style tag
     * 
     * @param  stdClass $item Item to render
     * @param  string $indent Indentation to use 
     * @return string
     */
    public function itemToString(stdClass $item, $indent)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if (!in_array($key, $this->_optionalAttributes)) {
                    continue;
                }
                if ('media' == $key) {
                    if (!in_array($value, $this->_mediaTypes)) {
                        continue;
                    }
                }
                $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
            }
        }

        $html = '<style type="text/css"' . $attrString . '>' . PHP_EOL
              . $indent . '<!--' . PHP_EOL . $indent . $item->content . PHP_EOL . $indent . '-->' . PHP_EOL
              . '</style>';

        return $html;
    }

    /**
     * Create string representation of placeholder
     * 
     * @param  string|int $indent 
     * @return string
     */
    public function toString($indent = null)
    {
        $indent = (null !== $indent)
                ? $this->_getWhitespace($indent)
                : $this->getIndent();

        $items = array();
        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }
            $items[] = $this->itemToString($item, $indent);
        }

        $return = $indent . implode($this->getSeparator() . $indent, $items);
        $return = preg_replace("/(\r\n?|\n)/", '$1' . $indent, $return);
        return $return;
    }

    /**
     * Create data item for use in stack
     * 
     * @param  string $content 
     * @param  array $attributes 
     * @return stdClass
     */
    public function createData($content, array $attributes)
    {
        if (!isset($attributes['media'])) {
            $attributes['media'] = 'screen';
        }

        $data = new stdClass();
        $data->content    = $content;
        $data->attributes = $attributes;

        return $data;
    }
}
