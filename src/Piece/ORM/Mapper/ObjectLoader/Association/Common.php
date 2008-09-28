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

namespace Piece::ORM::Mapper::ObjectLoader::Association;

use Piece::ORM::Mapper::Common as MapperCommon;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::ObjectLoader::Association::Common

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
abstract class Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $useMultipleIndexes = false;
    protected $defaultValueOfMappedAs;
    protected $associations;
    protected $associationKeys;
    protected $objects;
    protected $objectIndexes;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_mapper;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes properties with the given value.
     *
     * @param array                      $associations
     * @param array                      &$associationKeys
     * @param array                      &$objects
     * @param array                      &$objectIndexes
     * @param Piece::ORM::Mapper::Common $mapper
     */
    public function __construct(array $associations,
                                array &$associationKeys,
                                array &$objects,
                                array &$objectIndexes,
                                MapperCommon $mapper
                                )
    {
        $this->associations = $associations;
        $this->associationKeys = &$associationKeys;
        $this->objects = &$objects;
        $this->objectIndexes = &$objectIndexes;
        $this->_mapper = $mapper;
    }

    // }}}
    // {{{ prepareLoading()

    /**
     * Prepares loading associated objects.
     *
     * @param array   $row
     * @param integer $objectIndex
     * @param string  $mappedAs
     */
    public function prepareLoading(array $row, $objectIndex, $mappedAs)
    {
        $associationKeyFieldName = $this->getAssociationKeyFieldNameInPrimaryQuery($this->associations[$mappedAs]);
        $this->objects[$objectIndex]->$mappedAs = $this->defaultValueOfMappedAs;

        $this->associationKeys[$mappedAs][] = $this->_mapper->quote($row[$associationKeyFieldName], $associationKeyFieldName);

        if (!$this->useMultipleIndexes) {
            $this->objectIndexes[$mappedAs][ $row[$associationKeyFieldName] ] = $objectIndex;
        } else {
            $this->objectIndexes[$mappedAs][ $row[$associationKeyFieldName] ][] = $objectIndex;
        }
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all associated objects into appropriate objects.
     *
     * @param Piece::ORM::Mapper::Common $mapper
     * @param string                     $mappedAs
     */
    public function loadAll(MapperCommon $mapper, $mappedAs)
    {
        $mapper->setPreloadCallback($this->getPreloadCallback());
        $mapper->setPreloadCallbackArgs(array($mappedAs));
        $associatedObjects = $mapper->findAllWithQuery($this->buildQuery($mappedAs) . (is_null($this->associations[$mappedAs]['orderBy']) ? '' : " ORDER BY {$this->associations[$mappedAs]['orderBy']}"));
        $mapper->setPreloadCallback(null);
        $mapper->setPreloadCallbackArgs(null);

        $associationKeyPropertyName = Inflector::camelize($this->getAssociationKeyFieldNameInSecondaryQuery($this->associations[$mappedAs]), true);

        for ($j = 0, $count = count($associatedObjects); $j < $count; ++$j) {
            $this->associateObject($associatedObjects[$j], $mapper, $associationKeyPropertyName, $mappedAs);
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param string $mappedAs
     * @return string
     */
    abstract protected function buildQuery($mappedAs);

    // }}}
    // {{{ getAssociationKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the association key field in the primary query.
     *
     * @param array $association
     */
    abstract protected function getAssociationKeyFieldNameInPrimaryQuery(array $association);

    // }}}
    // {{{ getAssociationKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the association key field in the secondary query.
     *
     * @param array $association
     */
    abstract protected function getAssociationKeyFieldNameInSecondaryQuery(array $association);

    // }}}
    // {{{ associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass                   $associatedObject
     * @param Piece::ORM::Mapper::Common $mapper
     * @param string                     $associationKeyPropertyName
     * @param string                     $mappedAs
     */
    abstract protected function associateObject($associatedObject,
                                                MapperCommon $mapper,
                                                $associationKeyPropertyName,
                                                $mappedAs
                                                );

    // }}}
    // {{{ getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function getPreloadCallback()
    {
        return null;
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
