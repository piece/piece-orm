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

// {{{ Piece_ORM_Mapper_AssociatedObjectLoader_Common

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
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

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ prepareLoading()

    /**
     * Prepares loading associated objects.
     *
     * @param Piece_ORM_Mapper_ObjectLoader &$objectLoader
     * @param array                         $row
     * @param integer                       $i
     * @param integer                       $j
     */
    function prepareLoading(&$objectLoader, $row, $i, $j)
    {
        $relationships = $objectLoader->getRelationships();
        $relationshipKeyFieldName = $this->_getRelationshipKeyFieldNameInPrimaryQuery($relationships[$j]);

        $objects = &$objectLoader->getObjects();
        $objects[$i]->{$relationships[$j]['mappedAs']} = $this->_defaultValueOfMappedAs;

        $mapper = &$objectLoader->getMapper();
        $relationshipKeys = &$objectLoader->getRelationshipKeys();
        $relationshipKeys[$j][] = $mapper->quote($row[$relationshipKeyFieldName], $relationshipKeyFieldName);

        $objectIndexes = &$objectLoader->getObjectIndexes();
        if (!$this->_useMultipleIndexes) {
            $objectIndexes[$j][ $row[$relationshipKeyFieldName] ] = $i;
        } else {
            $objectIndexes[$j][ $row[$relationshipKeyFieldName] ][] = $i;
        }
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all associated objects into appropriate objects.
     *
     * @param Piece_ORM_Mapper_ObjectLoader &$objectLoader
     * @param integer                       $i
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function loadAll(&$objectLoader, $i)
    {
        $relationships = $objectLoader->getRelationships();
        $relationshipKeys = &$objectLoader->getRelationshipKeys();
        $mapper = &$objectLoader->getMapper();
        $associatedObjects = $mapper->findAllWithQuery($this->_buildQuery($relationships[$i], $relationshipKeys[$i]));
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($this->_getRelationshipKeyFieldNameInSecondaryQuery($relationships[$i]), true);
        $numberOfAssociatedObjects = count($associatedObjects);

        $objects = &$objectLoader->getObjects();
        $objectIndexes = &$objectLoader->getObjectIndexes();
        for ($j = 0; $j < $numberOfAssociatedObjects; ++$j) {
            $this->_associateObject($associatedObjects[$j], $objects, $objectIndexes[$i], $relationshipKeyPropertyName, $relationships[$i]['mappedAs']);
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
     * @param array    &$objects
     * @param array    $objectIndexes
     * @param string   $relationshipKeyPropertyName
     * @param string   $mappedAs
     * @abstract
     */
    function _associateObject(&$associatedObject, &$objects, $objectIndexes, $relationshipKeyPropertyName, $mappedAs) {}

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
