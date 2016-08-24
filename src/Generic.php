<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 18.10.2014
 * Time: 11:44
 */
namespace samsonframework\collection;

use samsonframework\core\RenderInterface;
use samsonframework\orm\QueryInterface;

/**
 * This class is a generic approach for rendering catalogs and lists,
 * it should be extended to match needs of specific project.
 *
 * @package samsonframework\collection
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
abstract class Generic implements \Iterator, RenderInterface
{
    /** @var array Collection */
    protected $collection = array();

    /** @var string Block view file */
    protected $indexView = 'www/index';

    /** @var string Item view file */
    protected $itemView = 'www/item';

    /** @var string Empty view file */
    protected $emptyView = 'www/empty';

    /** @var \samson\core\IViewable View render object */
    protected $renderer;

    /** @var  integer Collection size */
    protected $count = 0;

    /** @var QueryInterface Query  */
    protected $query;

    /**
     * Fill collection with items
     */
    abstract public function fill();

    /**
     * @return int Collection size
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Parent collection block render function
     * @param string $items Rendered items
     * @return string Rendered collection block
     */
    public function renderIndex($items)
    {
        return $this->renderer
            ->view($this->indexView)
            ->set('items', $items)
            ->output();
    }

    /**
     * Render collection item block
     * @param mixed $item Item to render
     * @return string Rendered collection item block
     */
    public function renderItem($item)
    {
        return $this->renderer
            ->view($this->itemView)
            ->set($item, 'item')
            ->output();
    }

    /**
     * Empty collection block render function
     * @return string Rendered empty collection block
     */
    public function renderEmpty()
    {
        return $this->renderer
            ->view($this->emptyView)
            ->output();
    }

    /**
     * Render material collection block
     * @return string Rendered material collection block
     */
    public function render()
    {
        $html = '';

        // Do not render block if there is no items
        if (sizeof($this->collection)) {
            // Render all block items
            foreach ($this->collection as &$item) {
                // Render item views
                $html .= $this->renderItem($item);
            }
            // Render block view
            $html = $this->renderIndex($html);

        } elseif (isset($this->emptyView{0})) { // Render empty view
            $html = $this->renderEmpty();
        }

        return $html;
    }

    /**
     * Generate collection of view variables, prefixed if needed, that should be passed to
     * view context.
     *
     * @param string $prefix Prefix to be added to all keys in returned data collection
     * @return array Collection(key => value) of data for view context
     */
    public function toView($prefix = '', array $restricted = null)
    {
        return array(
            $prefix.'html' => $this->render()
        );
    }

    /**
     * Generic collection constructor
     * @param RenderInterface $renderer View render object
     * @param QueryInterface $query Query object
     */
    public function __construct(RenderInterface $renderer, QueryInterface $query)
    {
        $this->query = $query;
        $this->renderer = $renderer;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->collection);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $key = key($this->collection);

        return ($key !== null && $key !== false);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->collection);
    }
}
