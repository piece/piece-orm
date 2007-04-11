<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM_Mapper_Common
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';
require_once 'MDB2.php';
require_once 'PEAR.php';

// {{{ Piece_ORM_Mapper_ObjectLoader

/**
 * An object loader for loading all objects with a result object.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM_Mapper_Common
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_ObjectLoader
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_mapper;
    var $_result;
    var $_relationships;
    var $_relationshipKeys = array();
    var $_objects = array();
    var $_objectIndexes = array();
    var $_associatedObjectLoaders = array();
    var $_loadedRows = array();
    var $_primaryKey;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes properties with the given values.
     *
     * @param Piece_ORM_Mapper_Common &$mapper
     * @param MDB2_Result             &$result
     * @param array                   $relationships
     */
    function Piece_ORM_Mapper_ObjectLoader(&$mapper, &$result, $relationships)
    {
        $this->_mapper = &$mapper;
        $this->_result = &$result;
        $this->_relationships = $relationships;

        if (count($this->_relationships)) {
            foreach (Piece_ORM_Mapper_RelationshipType::getRelationshipTypes() as $relationshipType) {
                $associatedObjectsLoaderClass = 'Piece_ORM_Mapper_AssociatedObjectLoader_' . ucwords($relationshipType);
                include_once str_replace('_', '/', $associatedObjectsLoaderClass) . '.php';
                $this->_associatedObjectLoaders[$relationshipType] = &new $associatedObjectsLoaderClass($this);
            }
        }

        $metadata = &$this->_mapper->getMetadata();
        $this->_primaryKey = $metadata->getPrimaryKey();
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all objects with a result object.
     *
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function loadAll()
    {
        $this->_loadPrimaryObjects();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $this->_loadAssociatedObjects();
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $this->_objects;
    }

    // }}}
    // {{{ getRelationships()

    /**
     * Gets the array of the relationships.
     *
     * @return array
     */
    function getRelationships()
    {
        return $this->_relationships;
    }

    // }}}
    // {{{ getObjects()

    /**
     * Gets the array of loaded objects.
     *
     * @return array
     */
    function &getObjects()
    {
        return $this->_objects;
    }

    // }}}
    // {{{ getMapper()

    /**
     * Gets the mapper object.
     *
     * @return mixed
     */
    function &getMapper()
    {
        return $this->_mapper;
    }

    // }}}
    // {{{ getRelationshipKeys()

    /**
     * Gets the array of the keys which are used for relationships.
     *
     * @return array
     */
    function &getRelationshipKeys()
    {
        return $this->_relationshipKeys;
    }

    // }}}
    // {{{ getObjectIndexes()

    /**
     * Gets the array of the indexes which indicate that target objects which
     * associated objects are loaded into.
     *
     * @return array
     */
    function &getObjectIndexes()
    {
        return $this->_objectIndexes;
    }

    function isLoadedRow($primaryKey)
    {
        return array_key_exists($primaryKey, $this->_loadedRows);
    }

    function addLoadedRow($primaryKey)
    {
        $this->_loadedRows[$primaryKey] = 1;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _load()

    /**
     * Loads an object with a row.
     *
     * @param array $row
     * @return stdClass
     */
    function &_load($row)
    {
        if (is_null($row)) {
            return $row;
        }

        if ($this->_primaryKey) {
            $loadedObject = &$this->_mapper->getLoadedObject($row[$this->_primaryKey]);
            if (!is_null($loadedObject)) {
                return $loadedObject;
            }
        }

        $object = &new stdClass();
        foreach ($row as $key => $value) {
            $propertyName = Piece_ORM_Inflector::camelize($key, true);
            $object->$propertyName = $value;
        }

        if ($this->_primaryKey) {
            $this->_mapper->addLoadedObject($row[$this->_primaryKey], $object);
        }

        return $object;
    }

    // }}}
    // {{{ _loadPrimaryObjects()

    /**
     * Loads all objects with a result object for the primary query.
     */
    function _loadPrimaryObjects()
    {
        $preloadCallback = $this->_mapper->getPreloadCallback();
        $preloadCallbackArgs = $this->_mapper->getPreloadCallbackArgs();
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        for ($i = 0; $row = &$this->_result->fetchRow(); ++$i) {
            if (MDB2::isError($row)) {
                PEAR::staticPopErrorHandling();
                Piece_ORM_Error::pushPEARError($row,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke MDB2_Driver_' . $this->_mapper->getDriverName() . '::fetchRow() for any reasons.'
                                               );
                return;
            }

            if (!is_null($preloadCallback)) {
                $loadObject = call_user_func_array($preloadCallback, array_merge(array(&$row, &$this->_mapper), $preloadCallbackArgs));
            } else {
                $loadObject = true;
            }

            if ($loadObject) {
                $this->_objects[] = &$this->_load($row);
            }

            for ($j = 0; $j < count($this->_relationships); ++$j) {
                $this->_associatedObjectLoaders[ $this->_relationships[$j]['type'] ]->prepareLoading($row, $i, $j);
            }
        }
        PEAR::staticPopErrorHandling();
    }

    // }}}
    // {{{ _loadAssociatedObjects()

    /**
     * Loads associated objects into appropriate objects.
     *
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _loadAssociatedObjects()
    {
        for ($i = 0; $i < count($this->_relationships); ++$i) {
            $mapper = &Piece_ORM_Mapper_Factory::factory(Piece_ORM_Inflector::camelize($this->_relationships[$i]['table']));
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }

            $this->_associatedObjectLoaders[ $this->_relationships[$i]['type'] ]->loadAll($mapper, $i);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }
    }

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>
