<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';

// {{{ Piece_ORM_Mapper_AssociatedObjectLoader_Common

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_AssociatedObjectLoader_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_useMultipleIndexes = false;
    var $_defaultValueOfMappedAs;
    var $_relationships;
    var $_relationshipKeys;
    var $_objects;
    var $_objectIndexes;
    var $_mapper;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes properties with the given value.
     *
     * @param array                         $relationships
     * @param array                         &$relationshipKeys
     * @param array                         &$objects
     * @param array                         &$objectIndexes
     * @param Piece_ORM_Mapper_Common       &$mapper
     */
    function Piece_ORM_Mapper_AssociatedObjectLoader_Common($relationships, &$relationshipKeys, &$objects, &$objectIndexes, &$mapper)
    {
        $this->_relationships = $relationships;
        $this->_relationshipKeys = &$relationshipKeys;
        $this->_objects = &$objects;
        $this->_objectIndexes = &$objectIndexes;
        $this->_mapper = &$mapper;
    }

    // }}}
    // {{{ prepareLoading()

    /**
     * Prepares loading associated objects.
     *
     * @param array   $row
     * @param integer $objectIndex
     * @param integer $relationshipIndex
     */
    function prepareLoading($row, $objectIndex, $relationshipIndex)
    {
        $relationshipKeyFieldName = $this->_getRelationshipKeyFieldNameInPrimaryQuery($this->_relationships[$relationshipIndex]);
        $this->_objects[$objectIndex]->{ $this->_relationships[$relationshipIndex]['mappedAs'] } = $this->_defaultValueOfMappedAs;

        $this->_relationshipKeys[$relationshipIndex][] = $this->_mapper->quote($row[$relationshipKeyFieldName], $relationshipKeyFieldName);

        if (!$this->_useMultipleIndexes) {
            $this->_objectIndexes[$relationshipIndex][ $row[$relationshipKeyFieldName] ] = $objectIndex;
        } else {
            $this->_objectIndexes[$relationshipIndex][ $row[$relationshipKeyFieldName] ][] = $objectIndex;
        }
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all associated objects into appropriate objects.
     *
     * @param Piece_ORM_Mapper_Common &$mapper
     * @param integer                 $relationshipIndex
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     */
    function loadAll(&$mapper, $relationshipIndex)
    {
        $mapper->setPreloadCallback($this->_getPreloadCallback());
        $mapper->setPreloadCallbackArgs(array($relationshipIndex));
        $associatedObjects = $mapper->findAllWithQuery($this->_buildQuery($relationshipIndex) . (is_null($this->_relationships[$relationshipIndex]['orderBy']) ? '' : " ORDER BY {$this->_relationships[$relationshipIndex]['orderBy']}"));
        $mapper->setPreloadCallback(null);
        $mapper->setPreloadCallbackArgs(null);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->_getRelationshipKeyFieldNameInSecondaryQuery($this->_relationships[$relationshipIndex]), true);

        for ($j = 0, $count = count($associatedObjects); $j < $count; ++$j) {
            $this->_associateObject($associatedObjects[$j], $mapper, $relationshipKeyPropertyName, $relationshipIndex);
        }
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param array $relationship
     * @param array &$relationshipKeys
     * @return string
     * @abstract
     */
    function _buildQuery($relationship, &$relationshipKeys) {}

    // }}}
    // {{{ _getRelationshipKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the relationship key field in the primary query.
     *
     * @param array $relationship
     * @abstract
     */
    function _getRelationshipKeyFieldNameInPrimaryQuery($relationship) {}

    // }}}
    // {{{ _getRelationshipKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the relationship key field in the secondary query.
     *
     * @param array $relationship
     * @abstract
     */
    function _getRelationshipKeyFieldNameInSecondaryQuery($relationship) {}

    // }}}
    // {{{ _associateObject()

    /**
     * Associates an object which are loaded by the secondary query into
     * objects which are loaded by the primary query.
     *
     * @param stdClass                &$associatedObject
     * @param Piece_ORM_Mapper_Common &$mapper
     * @param string                  $relationshipKeyPropertyName
     * @param integer                 $relationshipIndex
     */
    function _associateObject(&$associatedObject, &$mapper, $relationshipKeyPropertyName, $relationshipIndex) {}

    // }}}
    // {{{ _getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    function _getPreloadCallback()
    {
        return null;
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
