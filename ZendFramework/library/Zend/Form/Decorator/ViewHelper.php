<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Form
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Form_Decorator_Abstract */
require_once 'Zend/Form/Decorator/Abstract.php';

/**
 * Zend_Form_Decorator_ViewHelper
 *
 * Decorate an element by using a view helper to render it.
 *
 * Accepts the following options:
 * - separator: string with which to separate passed in content and generated content
 * - placement: whether to append or prepend the generated content to the passed in content
 * - helper:    the name of the view helper to use
 *
 * Assumes the view helper accepts three parameters, the name, value, and 
 * optional attributes; these will be provided by the element.
 * 
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Decorator
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: ViewHelper.php 7484 2008-01-18 14:18:19Z matthew $
 */
class Zend_Form_Decorator_ViewHelper extends Zend_Form_Decorator_Abstract
{
    /**
     * View helper to use when rendering
     * @var string
     */
    protected $_helper;

    /**
     * Set view helper to use when rendering
     * 
     * @param  string $helper 
     * @return Zend_Form_Decorator_Element_ViewHelper
     */
    public function setHelper($helper)
    {
        $this->_helper = (string) $helper;
        return $this;
    }

    /**
     * Retrieve view helper for rendering element
     *
     * @return string
     */
    public function getHelper()
    {
        if (null === $this->_helper) {
            $options = $this->getOptions();
            if (isset($options['helper'])) {
                $this->_helper = (string) $options['helper'];
            } else {
                $element = $this->getElement();
                if (null !== $element) {
                    $type = $element->getType();
                    if ($pos = strrpos($type, '_')) {
                        $type = substr($type, $pos + 1);
                    }
                    $this->_helper = 'form' . ucfirst($type);
                }
            }
        }

        return $this->_helper;
    }

    /**
     * Render an element using a view helper
     *
     * Determine view helper from 'viewHelper' option, or, if none set, from 
     * the element type. Then call as 
     * helper($element->getName(), $element->getValue(), $element->getAttribs())
     * 
     * @param  string $content
     * @return string
     * @throws Zend_Form_Decorator_Exception if element or view are not registered
     */
    public function render($content)
    {
        $element = $this->getElement();

        $view = $element->getView();
        if (null === $view) {
            require_once 'Zend/Form/Decorator/Exception.php';
            throw new Zend_Form_Decorator_Exception('ViewHelper decorator cannot render without a registered view object');
        }

        $helper         = $this->getHelper();
        $separator      = $this->getSeparator();
        $elementContent = $view->$helper($element->getName(), $element->getValue(), $element->getAttribs(), $element->options);
        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $separator . $elementContent;
            case self::PREPEND:
                return $elementContent . $separator . $content;
            default:
                return $elementContent;
        }
    }
}
