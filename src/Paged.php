<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 21.04.2015
 * Time: 9:59
 */
namespace samsonframework\collection;

use samsonframework\core\RenderInterface;
use samsonframework\pager\PagerInterface;
use samsonframework\orm\QueryInterface;

/**
 * Generic SamsonCMS application entities collection.
 * Class provide all basic UI interactions with database entities.
 *
 * @package samsonframework\collection
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class Paged extends Generic
{
    /** @var string Block view file */
    protected $indexView = 'www/collection/index';

    /** @var string Item view file */
    protected $itemView = 'www/collection/item/index';

    /** @var string Empty view file */
    protected $emptyView = 'www/collection/item/empty';

    /** @var  PagerInterface Pager object */
    protected $pager;

    /** @var array Collection for current collection entity identifiers */
    protected $entityIDs = array();

    /** @var array Collection of query handlers */
    protected $idHandlers = array();

    /** @var string Entity primary field name */
    protected $entityPrimaryField;

    /** @var array Sorter parameters collection */
    protected $sorter = array();

    /** @var array Search string collection */
    protected $search = array();

    /**
     * Generic collection constructor
     * @param RenderInterface $renderer View render object
     * @param QueryInterface $query Query object
     */
    public function __construct(RenderInterface $renderer, QueryInterface $query, PagerInterface $pager)
    {
        // Call parent initialization
        parent::__construct($renderer, $query);

        // Store pager object
        $this->pager = $pager;
    }

    /**
     * Render products collection block
     * @param string $prefix Prefix for view variables
     * @param array $restricted Collection of ignored keys
     * @return array Collection key => value
     */
    public function toView($prefix = null, array $restricted = array())
    {
        // Render pager and collection
        return array(
            $prefix.'html' => $this->render(),
            $prefix.'pager' => $this->pager->toHTML()
        );
    }

    /**
     * Filter collection using field values and LIKE relation.
     *
     * @param string $search Search string
     * @return $this Chaining
     */
    public function search($search)
    {
        // If input parameter is a string add it to search string collection
        if (isset($search{0})) {
            $this->search[] = $search;
        }

        // Chaining
        return $this;
    }

    /**
     * Add external identifier filter handler
     * @param callback $handler
     * @param array $params
     * @return $this Chaining
     */
    public function handler($handler, array $params = array())
    {
        if (is_callable($handler)) {
            // Add callback with parameters to array
            $this->idHandlers[] = array($handler, $params);
        }

        return $this;
    }

    /**
     * Set collection sorter parameters
     * @param string $field Entity field name
     * @param string $destination ASC|DESC
     * @return $this Chaining
     */
    public function sorter($field, $destination = 'ASC')
    {
        // TODO: We need query interface to return entity fields for checking if exists
        $this->sorter[] = array(
            $field,
            $destination
        );

        return $this;
    }

    /**
     * Call handlers stack
     * @param array $handlers Collection of callbacks with their parameters
     * @param array $params External parameters to pass to callback at first
     * @return bool True if all handlers succeeded
     */
    protected function callHandlers(& $handlers = array(), $params = array())
    {
        // Call external handlers
        foreach ($handlers as $handler) {
            // Call external handlers chain
            if (
                call_user_func_array(
                    $handler[0],
                    array_merge($params, $handler[1]) // Merge params and handler params
                ) === false
            ) {
                // Stop - if one of external handlers has failed
                return false;
            }
        }

        return true;
    }

    /**
     * Fill collection with data from database
     * @return $this Chaining
     */
    public function fill()
    {
        // Clear current entity identifiers
        $this->entityIDs = array();

        // If we have no external entity identifier handlers
        if (!sizeof($this->idHandlers)) {
            // Lets retrieve all possible entities identifiers from database
            $this->entityIDs = $this->query->fieldsNew($this->entityPrimaryField);
        } else {// First of all call all external entity identifier handlers
            $this->callHandlers($this->idHandlers, array(&$this->entityIDs));
        }

        /* TODO: We need query interface to return entity fields to iterate them and add conditions to query
        // Apply all search filters filters
        if (sizeof($this->search)) {
            // Concat all search works into one string for searching
            $search = implode(' ', $this->search);

            // Create OR condition group
            $cond = Condition('or');

            // Iterate all entity fields and create one or with LIKE condition
            foreach ($this->query->fields() as $field) {
                $cond->add(new Condition($field, $search, dbRelation::LIKE));
            }

            // Perform database query to get entity identifiers matching search request
            $this->entityIDs = $this->query->cond($cond)->exec();
        }
        */

            // Apply all sorter to request before cutting array into  pages
        if (sizeof($this->sorter)) {
            foreach ($this->sorter as $sorter) {
                $this->query->order_by($sorter[0], $sorter[1]);
            }
        }

        // Finally get all sorted entity objects by their filtered identifiers
        if (
            $this->query
            ->cond($this->entityPrimaryField, $this->entityIDs)
            ->fieldsNew($this->entityPrimaryField, $this->entityIDs)
        ) {
            // Recount pager
            $this->pager->update(sizeof($this->entityIDs));

            // Cut only needed entity identifiers from array
            $this->entityIDs = array_slice($this->entityIDs, $this->pager->start, $this->pager->end);

            // Retrieve all entities from database with passed identifiers limited by pager
            $this->collection = $this->query->cond($this->entityPrimaryField, $this->entityIDs)->exec();
        }

        return $this;
    }
}
