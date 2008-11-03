<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
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

namespace Piece::ORM::Mapper::ObjectLoader::AssociationLoaderStrategy;

use Piece::ORM::Mapper::ObjectLoader::AssociationLoaderStrategy::AbstractAssociationLoaderStrategy;
use Piece::ORM::Mapper;
use Piece::ORM::Mapper::Association;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::ObjectLoader::AbstractAssociationLoaderStrategy::ManyToMany

/**
 * An associated object loader for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ManyToMany extends AbstractAssociationLoaderStrategy
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $defaultValueOfProperty = array();
    protected $associations = array();

    /**#@-*/

    /**#@+
     * @access private
     */

    private $loadedRows = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ addAssociation()

    /**
     * Adds an association about what an inverse side record is associated with
     * an owning side record.
     *
     * @param array              &$row
     * @param Piece::ORM::Mapper $mapper
     * @param integer            $associationIndex
     * @return boolean
     */
    public function addAssociation(&$row, Mapper $mapper, $associationIndex)
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = $metadata->getPrimaryKey();
        $this->_associations[$associationIndex][ $row[$primaryKey] ][] = $row[ $this->_getAssociationKeyFieldInSecondaryQuery($this->associations[$associationIndex]) ];

        if (@array_key_exists($row[$primaryKey], $this->_loadedRows[$associationIndex])) {
            return false;
        } else {
            @$this->_loadedRows[$associationIndex][ $row[$primaryKey] ] = true;
            unset($row[ $this->_getAssociationKeyFieldInSecondaryQuery($this->associations[$associationIndex]) ]);
            return true;
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ _buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param integer $associationIndex
     * @return string
     */
    protected function _buildQuery($associationIndex)
    {
        $association = $this->associations[$associationIndex];
        $linkTable = $association->getLinkTable();
        return 'SELECT ' .
            $linkTable->getTable() .
            '.' .
            $linkTable->getColumn() .
            ' AS ' .
            $this->_getAssociationKeyFieldInSecondaryQuery($association) .
            ', ' .
            $association->getTable() .
            '.* FROM ' .
            $association->getTable() .
            ', ' .
            $linkTable->getTable() .
            ' WHERE ' .
            $linkTable->getTable() .
            '.' .
            $linkTable->getColumn() .
            ' IN (' .
            implode(',', $this->associationKeys[$associationIndex]) .
            ') AND ' .
            $association->getTable() .
            '.' .
            $association->getColumn() .
            ' = ' .
            $linkTable->getTable() .
            '.' .
            $linkTable->getInverseColumn();
    }

    // }}}
    // {{{ _getAssociationKeyFieldInPrimaryQuery()

    /**
     * Gets the name of the association key field in the primary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    protected function _getAssociationKeyFieldInPrimaryQuery(Association $association)
    {
        return $association->getLinkTable()->getReferencedColumn();
    }

    // }}}
    // {{{ _getAssociationKeyFieldInSecondaryQuery()

    /**
     * Gets the name of the association key field in the secondary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    protected function _getAssociationKeyFieldInSecondaryQuery(Association $association)
    {
        return '__relationship_key_field';
    }

    // }}}
    // {{{ _associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass           $associatedObject
     * @param Piece::ORM::Mapper $mapper
     * @param string             $associationKeyProperty
     * @param integer            $associationIndex
     */
    protected function _associateObject($associatedObject, Mapper $mapper, $associationKeyProperty, $associationIndex)
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = Inflector::camelize($metadata->getPrimaryKey(), true);

        for ($j = 0, $count = count($this->_associations[$associationIndex][ $associatedObject->$primaryKey ]); $j < $count; ++$j) {
            $this->objects[ $this->objectIndexes[$associationIndex][ $this->_associations[$associationIndex][ $associatedObject->$primaryKey ][$j] ] ]->{ $this->associations[$associationIndex]->getProperty() }[] = $associatedObject;
        }
    }

    // }}}
    // {{{ _getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function _getPreloadCallback()
    {
        return array($this, 'addAssociation');
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
