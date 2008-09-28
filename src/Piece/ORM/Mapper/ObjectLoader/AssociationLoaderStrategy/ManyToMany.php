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
use Piece::ORM::Mapper::AbstractMapper;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::ObjectLoader::AssociationLoaderStrategy::ManyToMany

/**
 * An associated object loader for Many-to-Many associations.
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

    protected $defaultValueOfMappedAs = array();

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_associations = array();
    private $_loadedRows = array();

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
     * @param array                              $row
     * @param Piece::ORM::Mapper::AbstractMapper $mapper
     * @param string                             $mappedAs
     * @return boolean
     */
    public function addAssociation(array $row, AbstractMapper $mapper, $mappedAs)
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = $metadata->getPrimaryKey();
        $this->_associations[$mappedAs][ $row[$primaryKey] ][] = $row[ $this->getAssociationKeyFieldNameInSecondaryQuery($this->associations[$mappedAs]) ];

        if (@array_key_exists($row[$primaryKey], $this->_loadedRows[$mappedAs])) {
            return false;
        } else {
            @$this->_loadedRows[$mappedAs][ $row[$primaryKey] ] = true;
            unset($row[ $this->getAssociationKeyFieldNameInSecondaryQuery($this->associations[$mappedAs]) ]);
            return true;
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param string $mappedAs
     * @return string
     */
    protected function buildQuery($mappedAs)
    {
        return "SELECT {$this->associations[$mappedAs]['through']['table']}.{$this->associations[$mappedAs]['through']['column']} AS " . $this->getAssociationKeyFieldNameInSecondaryQuery($this->associations[$mappedAs]) . ", {$this->associations[$mappedAs]['table']}.* FROM {$this->associations[$mappedAs]['table']}, {$this->associations[$mappedAs]['through']['table']} WHERE {$this->associations[$mappedAs]['through']['table']}.{$this->associations[$mappedAs]['through']['column']} IN (" . implode(',', $this->associationKeys[$mappedAs]) . ") AND {$this->associations[$mappedAs]['table']}.{$this->associations[$mappedAs]['column']} = {$this->associations[$mappedAs]['through']['table']}.{$this->associations[$mappedAs]['through']['inverseColumn']}";
    }

    // }}}
    // {{{ getAssociationKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the association key field in the primary query.
     *
     * @param array $association
     * @return string
     */
    protected function getAssociationKeyFieldNameInPrimaryQuery(array $association)
    {
        return $association['through']['referencedColumn'];
    }

    // }}}
    // {{{ getAssociationKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the association key field in the secondary query.
     *
     * @param array $association
     * @return string
     */
    protected function getAssociationKeyFieldNameInSecondaryQuery(array $association)
    {
        return "__association_key_field";
    }

    // }}}
    // {{{ associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass                           $associatedObject
     * @param Piece::ORM::Mapper::AbstractMapper $mapper
     * @param string                             $associationKeyPropertyName
     * @param string                             $mappedAs
     */
    protected function associateObject($associatedObject,
                                       AbstractMapper $mapper,
                                       $associationKeyPropertyName,
                                       $mappedAs
                                       )
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = Inflector::camelize($metadata->getPrimaryKey(), true);

        for ($j = 0, $count = count($this->_associations[$mappedAs][ $associatedObject->$primaryKey ]); $j < $count; ++$j) {
            $this->objects[ $this->objectIndexes[$mappedAs][ $this->_associations[$mappedAs][ $associatedObject->$primaryKey ][$j] ] ]->{$mappedAs}[] = $associatedObject;
        }
    }

    // }}}
    // {{{ getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function getPreloadCallback()
    {
        return array($this, 'addAssociation');
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
