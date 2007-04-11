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

require_once 'Piece/ORM/Mapper/AssociatedObjectLoader/Common.php';

// {{{ Piece_ORM_Mapper_AssociatedObjectLoader_ManyToMany

/**
 * An associated object loader for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_AssociatedObjectLoader_ManyToMany extends Piece_ORM_Mapper_AssociatedObjectLoader_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_defaultValueOfMappedAs = array();
    var $_associations = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ addAssociation()

    /**
     * Adds an association about what an inverse side record is associated
     * with an owning side record.
     *
     * @param array &$row
     * @param mixed &$mapper
     * @param array $relationship
     */
    function addAssociation(&$row, &$mapper, $relationship)
    {
        $metadata = &$mapper->getMetadata();
        $primaryKey = $metadata->getPrimaryKey();
        $this->_associations[ $row[$primaryKey] ][] = $row[ $this->_getRelationshipKeyFieldNameInSecondaryQuery($relationship) ];

        if ($this->_objectLoader->isLoadedRow($row[$primaryKey])) {
            return false;
        } else {
            $this->_objectLoader->addLoadedRow($row[$primaryKey]);
            return true;
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
     */
    function _buildQuery($relationship, &$relationshipKeys)
    {
        return "SELECT {$relationship['through']['table']}.{$relationship['through']['column']} AS " . $this->_getRelationshipKeyFieldNameInSecondaryQuery($relationship) . ", {$relationship['table']}.* FROM {$relationship['table']}, {$relationship['through']['table']} WHERE {$relationship['through']['table']}.{$relationship['through']['column']} IN (" . implode(',', $relationshipKeys) . ") AND {$relationship['table']}.{$relationship['column']} = {$relationship['through']['table']}.{$relationship['through']['inverseColumn']}";
    }

    // }}}
    // {{{ _getRelationshipKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the relationship key field in the primary query.
     *
     * @param array $relationship
     * @return string
     */
    function _getRelationshipKeyFieldNameInPrimaryQuery($relationship)
    {
        return $relationship['through']['referencedColumn'];
    }

    // }}}
    // {{{ _getRelationshipKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the relationship key field in the secondary query.
     *
     * @param array $relationship
     * @return string
     */
    function _getRelationshipKeyFieldNameInSecondaryQuery($relationship)
    {
        return "{$relationship['through']['table']}_{$relationship['through']['column']}";
    }

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
     * @param mixed    &$mapper
     */
    function _associateObject(&$associatedObject, &$objects, $objectIndexes, $relationshipKeyPropertyName, $mappedAs, &$mapper)
    {
        $metadata = &$mapper->getMetadata();
        $primaryKey = $metadata->getPrimaryKey();
        for ($i = 0; $i < count($this->_associations[ $associatedObject->$primaryKey ]); ++$i ) {
            $objects[ $objectIndexes[ $this->_associations[ $associatedObject->$primaryKey ][$i] ] ]->{$mappedAs}[] = &$associatedObject;
        }
    }

    // }}}
    // {{{ _getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    function _getPreloadCallback()
    {
        return array(&$this, 'addAssociation');
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
