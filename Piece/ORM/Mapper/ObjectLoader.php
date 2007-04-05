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
        $numberOfRelationships = count($this->_relationship);
        $relationshipKeys = array();
        $objects = array();
        $objectsIndexes = array();
        $error = null;

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        for ($i = 0; $row = &$this->_result->fetchRow(); ++$i) {
            if (MDB2::isError($row)) {
                $error = &$row;
                break;
            }

            $objects[] = &$this->_mapper->_load($row);
            for ($k = 0; $k < $numberOfRelationships; ++$k) {
                $objects[$i]->{$this->_relationship[$k]['mappedAs']} = array();
                if ($this->_relationship[$k]['type'] == 'manyToMany') {
                    $relationshipKeys[$k][] = $this->_mapper->_dbh->quote($row[ $this->_relationship[$k]['through']['referencedColumn'] ],
                                                                 $this->_mapper->_metadata->getDatatype($this->_relationship[$k]['through']['referencedColumn'])
                                                                 );
                    $objectsIndexes[$k][ $row[ $this->_relationship[$k]['through']['referencedColumn'] ] ] = $i;
                } elseif ($this->_relationship[$k]['type'] == 'oneToMany') {
                    $relationshipKeys[$k][] = $this->_mapper->_dbh->quote($row[ $this->_relationship[$k]['referencedColumn'] ],
                                                                 $this->_mapper->_metadata->getDatatype($this->_relationship[$k]['referencedColumn'])
                                                                 );
                    $objectsIndexes[$k][ $row[ $this->_relationship[$k]['referencedColumn'] ] ] = $i;
                }
            }
        }
        PEAR::staticPopErrorHandling();

        if (MDB2::isError($error)) {
            Piece_ORM_Error::pushPEARError($error,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->_mapper->_getDriverName() . '::fetchRow() for any reasons.'
                                           );
            return;
        }

        for ($i = 0; $i < $numberOfRelationships; ++$i) {
            if ($this->_relationship[$i]['type'] == 'manyToMany') {
                $relationshipKeyFieldName = "{$this->_relationship[$i]['through']['table']}_{$this->_relationship[$i]['through']['column']}";
                $query = "SELECT {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['column']} AS $relationshipKeyFieldName, {$this->_relationship[$i]['table']}.* FROM {$this->_relationship[$i]['table']}, {$this->_relationship[$i]['through']['table']} WHERE {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['column']} IN (" . implode(',', $relationshipKeys[$i]) . ") AND {$this->_relationship[$i]['table']}.{$this->_relationship[$i]['column']} = {$this->_relationship[$i]['through']['table']}.{$this->_relationship[$i]['through']['inverseColumn']}";
                $associatedObjects = $this->_mapper->_findAllWithQuery($query);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $numberOfAssociatedObjects = count($associatedObjects);
                $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($relationshipKeyFieldName, true);
                for ($k = 0; $k < $numberOfAssociatedObjects; ++$k) {
                    $objects[ $objectsIndexes[$i][ $associatedObjects[$k]->$relationshipKeyPropertyName ] ]->{$this->_relationship[$i]['mappedAs']}[] = &$associatedObjects[$k];
                    unset($associatedObjects[$k]->$relationshipKeyPropertyName);
                }
            } elseif ($this->_relationship[$i]['type'] == 'oneToMany') {
                $query = "SELECT * FROM {$this->_relationship[$i]['table']} WHERE {$this->_relationship[$i]['column']} IN (" . implode(',', $relationshipKeys[$i]) . ')';
                $associatedObjects = $this->_mapper->_findAllWithQuery($query);
                if (Piece_ORM_Error::hasErrors('exception')) {
                    return;
                }

                $numberOfAssociatedObjects = count($associatedObjects);
                $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->_relationship[$i]['column'], true);
                for ($k = 0; $k < $numberOfAssociatedObjects; ++$k) {
                    $objects[ $objectsIndexes[$i][ $associatedObjects[$k]->$relationshipKeyPropertyName ] ]->{$this->_relationship[$i]['mappedAs']}[] = &$associatedObjects[$k];
                }
            }
        }

        return $objects;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

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
