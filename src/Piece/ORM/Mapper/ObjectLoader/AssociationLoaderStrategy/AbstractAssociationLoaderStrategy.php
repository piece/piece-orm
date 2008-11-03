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

use Piece::ORM::Inflector;
use Piece::ORM::Mapper;
use Piece::ORM::Mapper::Association;

// {{{ Piece::ORM::Mapper::ObjectLoader::AbstractAssociationLoaderStrategy

/**
 * The base class for associated object loaders.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
abstract class AbstractAssociationLoaderStrategy
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $ueMultipleIndexes = false;
    protected $defaultValueOfProperty;
    protected $associations;
    protected $associationKeys;
    protected $objects;
    protected $objectIndexes;
    protected $mapper;

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes properties with the given value.
     *
     * @param array              $associations
     * @param array              &$associationKeys
     * @param array              &$objects
     * @param array              &$objectIndexes
     * @param Piece::ORM::Mapper $mapper
     */
    public function __construct($associations,
                                &$associationKeys,
                                &$objects,
                                &$objectIndexes,
                                Mapper $mapper
                                )
    {
        $this->associations = $associations;
        $this->associationKeys = &$associationKeys;
        $this->objects = &$objects;
        $this->objectIndexes = &$objectIndexes;
        $this->mapper = $mapper;
    }

    // }}}
    // {{{ prepareLoading()

    /**
     * Prepares loading associated objects.
     *
     * @param array   $row
     * @param integer $objectIndex
     * @param integer $associationIndex
     */
    public function prepareLoading($row, $objectIndex, $associationIndex)
    {
        $associationKeyField = $this->_getAssociationKeyFieldInPrimaryQuery($this->associations[$associationIndex]);
        $this->objects[$objectIndex]->{ $this->associations[$associationIndex]->getProperty() } = $this->defaultValueOfProperty;

        $this->associationKeys[$associationIndex][] = $this->mapper->quote($row[$associationKeyField], $associationKeyField);

        if (!$this->ueMultipleIndexes) {
            $this->objectIndexes[$associationIndex][ $row[$associationKeyField] ] = $objectIndex;
        } else {
            $this->objectIndexes[$associationIndex][ $row[$associationKeyField] ][] = $objectIndex;
        }
    }

    // }}}
    // {{{ loadAll()

    /**
     * Loads all associated objects into appropriate objects.
     *
     * @param Piece_ORM_Mapper_Common $mapper
     * @param integer                 $associationIndex
     */
    public function loadAll($mapper, $associationIndex)
    {
        $mapper->setPreloadCallback($this->_getPreloadCallback());
        $mapper->setPreloadCallbackArgs(array($associationIndex));
        $associatedObjects =
            $mapper->findAllWithQuery($this->_buildQuery($associationIndex) .
                                      (is_null($this->associations[$associationIndex]->getOrderBy()) ? ''
                                       : ' ORDER BY ' . $this->associations[$associationIndex]->getOrderBy())
                                      );
        $mapper->setPreloadCallback(null);
        $mapper->setPreloadCallbackArgs(null);

        $associationKeyProperty = Inflector::camelize($this->_getAssociationKeyFieldInSecondaryQuery($this->associations[$associationIndex]), true);

        for ($j = 0, $count = count($associatedObjects); $j < $count; ++$j) {
            $this->_associateObject($associatedObjects[$j], $mapper, $associationKeyProperty, $associationIndex);
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
     */
    abstract protected function _buildQuery($associationIndex);

    // }}}
    // {{{ _getAssociationKeyFieldInPrimaryQuery()

    /**
     * Gets the name of the association key field in the primary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    abstract protected function _getAssociationKeyFieldInPrimaryQuery(Association $association);

    // }}}
    // {{{ _getAssociationKeyFieldInSecondaryQuery()

    /**
     * Gets the name of the association key field in the secondary query.
     *
     * @param Piece::ORM::Mapper::Association $association
     */
    abstract protected function _getAssociationKeyFieldInSecondaryQuery(Association $association);

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
    abstract protected function _associateObject($associatedObject, Mapper $mapper, $associationKeyProperty, $associationIndex);

    // }}}
    // {{{ _getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function _getPreloadCallback()
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
