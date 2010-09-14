<?php
namespace Sonic;

/**
 * Layout - it's a view that holds another view
 *
 * @package Sonic
 * @subpackage Layout
 * @author Craig Campbell
 */
class Layout extends View
{
    /**
     * @var string
     */
    const MAIN = 'main';

    /**
     * @var View
     */
    protected $_top_view;

    /**
     * @var string
     */
    protected static $_title_pattern;

    /**
     * buffers the top view before outputting the layout
     *
     * @return void
     */
    public function output()
    {
        if ($this->topView() !== null) {
            $this->topView()->buffer();
        }
        parent::output();
    }

    /**
     * sets or gets the top level view
     *
     * @param mixed $view
     * @return View
     */
    public function topView(View $view = null)
    {
        if ($this->_top_view === null && $view !== null) {
            $this->_top_view = $view;
        }
        return $this->_top_view;
    }

    /**
     * sets title pattern
     *
     * @param string (something like "my application - {{title}}")
     * @return string
     */
    public function setTitlePattern($pattern)
    {
        self::$_title_pattern = $pattern;
        return $this->getTitle($this->topView()->title());
    }

    /**
     * gets a title for a view
     *
     * @param string
     * @return string
     */
    public function getTitle($string)
    {
        if (!self::$_title_pattern) {
            return $string;
        }
        return str_replace('${title}', $string, self::$_title_pattern);
    }

    /**
     * gets the url for no turbo
     *
     * @return string
     */
    public function noTurboUrl()
    {
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '?') !== false) {
            return $uri . '&amp;noturbo=1';
        }
        return $uri . '?noturbo=1';
    }

    /**
     * outputs the turbo json
     *
     * @return string
     */
    public function turbo()
    {
        flush();
        return App::getInstance()->processViewQueue();
    }
}
