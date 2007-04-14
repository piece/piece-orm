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
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';

// {{{ Piece_ORM_Mapper_AssociatedObjectLoader_Common

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
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
    var $_objectLoader;
    var $_relationships;
    var $_relationshipKeys;
    var $_objects;
    var $_objectIndexes;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes properties with the given value.
     *
     * @param Piece_ORM_Mapper_ObjectLoader &$objectLoader
     */
    function Piece_ORM_Mapper_AssociatedObjectLoader_Common(&$objectLoader)
    {
        $this->_relationships = $objectLoader->getRelationships();
        $this->_relationshipKeys = &$objectLoader->getRelationshipKeys();
        $this->_objects = &$objectLoader->getObjects();
        $this->_objectIndexes = &$objectLoader->getObjectIndexes();
        $this->_objectLoader = &$objectLoader;
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
        $this->_objects[$objectIndex]->{$this->_relationships[$relationshipIndex]['mappedAs']} = $this->_defaultValueOfMappedAs;

        $mapper = &$this->_objectLoader->getMapper();
        $this->_relationshipKeys[$relationshipIndex][] = $mapper->quote($row[$relationshipKeyFieldName], $relationshipKeyFieldName);

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
     * @param mixed   &$mapper
     * @param integer $relationshipIndex
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function loadAll(&$mapper, $relationshipIndex)
    {
        $mapper->setPreloadCallback($this->_getPreloadCallback());
        $mapper->setPreloadCallbackArgs(array($relationshipIndex));

        $associatedObjects = $mapper->findAllWithQuery($this->_buildQuery($relationshipIndex) . (is_null($this->_relationships[$relationshipIndex]['orderBy']) ? '' : " ORDER BY {$this->_relationships[$relationshipIndex]['orderBy']}"));
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->_getRelationshipKeyFieldNameInSecondaryQuery($this->_relationships[$relationshipIndex]), true);

        for ($j = 0; $j < count($associatedObjects); ++$j) {
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
     * @param stdClass &$associatedObject
     * @param mixed    &$mapper
     * @param string   $relationshipKeyPropertyName
     * @param integer  $relationshipIndex
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
