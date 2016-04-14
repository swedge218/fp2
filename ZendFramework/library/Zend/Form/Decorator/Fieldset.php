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
 * @subpackage Decorator
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Form_Decorator_Abstract */
require_once 'Zend/Form/Decorator/Abstract.php';

/**
 * Zend_Form_Decorator_Fieldset
 *
 * Any options passed will be used as HTML attributes of the fieldset tag.
 * 
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Decorator
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Fieldset.php 7563 2008-01-22 17:26:07Z matthew $
 */
class Zend_Form_Decorator_Fieldset extends Zend_Form_Decorator_Abstract
{
    /**
     * Default placement: surround content
     * @var string
     */
    protected $_placement = null;

    /**
     * Get options
     *
     * Merges in element attributes as well.
     * 
     * @return array
     */
    public function getOptions()
    {
        $options = parent::getOptions();
        if (null !== ($element = $this->getElement())) {
            $attribs = $element->getAttribs();
            $options = array_merge($options, $attribs);
            $this->setOptions($options);
        }
        return $options;
    }

    /**
     * Render a fieldset
     * 
     * @param  string $content 
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $legend  = null;
        if (method_exists($element, 'getLegend')) {
            $legend = $element->getLegend();
        }

        if ((null !== $legend) && (null !== ($translator = $element->getTranslator()))) {
            $legend = $translator->translate($legend);
        }

        $attribs = $this->getOptions();
        $attribs['legend'] = $legend;
        return $view->fieldset($element->getName(), $content, $attribs);
    }
}
