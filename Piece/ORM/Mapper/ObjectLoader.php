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
    var $_relationship;
    var $_numberOfRelationships;
    var $_relationshipKeys = array();
    var $_objects = array();
    var $_objectsIndexes = array();

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
     * @param array                   $relationship
     */
    function Piece_ORM_Mapper_ObjectLoader(&$mapper, &$result, $relationship)
    {
        $this->_mapper = &$mapper;
        $this->_result = &$result;
        $this->_relationship = $relationship;
        $this->_numberOfRelationships = count($this->_relationship);
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

        $object = &new stdClass();
        foreach ($row as $key => $value) {
            $propertyName = Piece_ORM_Inflector::camelize($key, true);
            $object->$propertyName = $value;
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

            $this->_objects[] = &$this->_load($row);
            for ($j = 0; $j < $this->_numberOfRelationships; ++$j) {
                $this->_objects[$i]->{$this->_relationship[$j]['mappedAs']} = array();
                if ($this->_relationship[$j]['type'] == 'manyToMany') {
                    $this->_relationshipKeys[$j][] = $this->_mapper->quote($row[ $this->_relationship[$j]['through']['referencedColumn'] ],
                                                                           $this->_relationship[$j]['through']['referencedColumn']
                                                                           );
                    $this->_objectsIndexes[$j][ $row[ $this->_relationship[$j]['through']['referencedColumn'] ] ] = $i;
                } elseif ($this->_relationship[$j]['type'] == 'oneToMany') {
                    $this->_relationshipKeys[$j][] = $this->_mapper->quote($row[ $this->_relationship[$j]['referencedColumn'] ],
                                                                           $this->_relationship[$j]['referencedColumn']
                                                                           );
                    $this->_objectsIndexes[$j][ $row[ $this->_relationship[$j]['referencedColumn'] ] ] = $i;
                }
            }
        }
        PEAR::staticPopErrorHandling();
    }

    // }}}
    // {{{ _loadAssociatedObjects()

    /**
     * Loads associated objects into appropriate objects.
     */
    function _loadAssociatedObjects()
    {
        for ($i = 0; $i < $this->_numberOfRelationships; ++$i) {
            if ($this->_relationship[$i]['type'] == 'manyToMany') {
                $relationshipKeyFieldName = "{$this->_relationship[$i]['through']['table']}_{$this->_relationship[$i]['through']['column']}";
                $query = "SELECT {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['column']} AS $relationshipKeyFieldName, {$this->_relationship[$i]['table']}.* FROM {$this->_relationship[$i]['table']}, {$this->_relationship[$i]['through']['table']} WHERE {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['column']} IN (" . implode(',', $this->_relationshipKeys[$i]) . ") AND {$this->_relationship[$i]['table']}.{$this->_relationship[$i]['column']} = {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['inverseColumn']}";
                $associatedObjects = $this->_mapper->findAllWithQuery($query);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $numberOfAssociatedObjects = count($associatedObjects);
                $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($relationshipKeyFieldName, true);
                for ($j = 0; $j < $numberOfAssociatedObjects; ++$j) {
                    $this->_objects[ $this->_objectsIndexes[$i][ $associatedObjects[$j]->$relationshipKeyPropertyName ] ]->{$this->_relationship[$i]['mappedAs']}[] = &$associatedObjects[$j];
                    unset($associatedObjects[$j]->$relationshipKeyPropertyName);
                }
            } elseif ($this->_relationship[$i]['type'] == 'oneToMany') {
                $query = "SELECT * FROM {$this->_relationship[$i]['table']} WHERE {$this->_relationship[$i]['column']} IN (" . implode(',', $this->_relationshipKeys[$i]) . ')';
                $associatedObjects = $this->_mapper->findAllWithQuery($query);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $numberOfAssociatedObjects = count($associatedObjects);
                $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->_relationship[$i]['column'], true);
                for ($j = 0; $j < $numberOfAssociatedObjects; ++$j) {
                    $this->_objects[ $this->_objectsIndexes[$i][ $associatedObjects[$j]->$relationshipKeyPropertyName ] ]->{$this->_relationship[$i]['mappedAs']}[] = &$associatedObjects[$j];
                }
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
