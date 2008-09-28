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

namespace Piece::ORM::Mapper::Generator::AssociationNormalizerStrategy;

use Piece::ORM::Metadata;
use Piece::ORM::Exception;
use Piece::ORM::Metadata::MetadataFactory;

// {{{ Piece::ORM::Mapper::Generator::AssociationNormalizerStrategy::AbstractAssociationNormalizerStrategy

/**
 * The base class for association generators.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
abstract class AbstractAssociationNormalizerStrategy
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $association;
    protected $metadata;
    protected $associationMetadata;
    protected $referencedColumnRequired = true;

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
     * Initializes properties with the given values.
     *
     * @param array                $association
     * @param Piece::ORM::Metadata $metadata
     */
    public function __construct($association, Metadata $metadata)
    {
        $this->association = $association;
        $this->metadata = $metadata;
    }

    // }}}
    // {{{ normalize()

    /**
     * Normalizes a association definition.
     *
     * @return array
     * @throws Piece::ORM::Exception
     */
    public function normalize()
    {
        if (!array_key_exists('table', $this->association)) {
            throw new Exception('The element [ table ] is required to generate a association property declaration.');
        }

        $this->associationMetadata = MetadataFactory::factory($this->association['table']);

        if ($this->checkHavingSinglePrimaryKey()) {
            if (!$this->associationMetadata->getPrimaryKey()) {
                throw new Exception('A single primary key field is required in the table [ ' . $this->associationMetadata->getTableName(true) . ' ].');
            }
        }

        if (!array_key_exists('column', $this->association)) {
            if (!$this->normalizeColumn()) {
                throw new Exception('A single primary key field is required, if the element [ column ] in the element [ association ] omit.');
            }
        } 

        if (!$this->associationMetadata->hasField($this->association['column'])) {
            throw new Exception("The field [ {$this->association['column']} ] not found in the table [ " . $this->associationMetadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('referencedColumn', $this->association)) {
            if (!$this->normalizeReferencedColumn()) {
                throw new Exception('A single primary key field is required, if the element [ referencedColumn ] in the element [ association ] omit.');
            }
        } 

        if ($this->referencedColumnRequired && !$this->metadata->hasField($this->association['referencedColumn'])) {
            throw new Exception("The field [ {$this->association['referencedColumn']} ] not found in the table [ " . $this->metadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('orderBy', $this->association)) {
            $this->association['orderBy'] = null;
        }

        $this->normalizeOrderBy();
        $this->normalizeThrough();

        return $this->association;
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ normalizeThrough()

    /**
     * Normalizes "through" definition.
     */
    protected function normalizeThrough() {}

    // }}}
    // {{{ normalizeColumn()

    /**
     * Normalizes "column" definition.
     *
     * @return boolean
     */
    abstract protected function normalizeColumn();

    // }}}
    // {{{ normalizeReferencedColumn()

    /**
     * Normalizes "referencedColumn" definition.
     *
     * @return boolean
     */
    abstract protected function normalizeReferencedColumn();

    // }}}
    // {{{ normalizeOrderBy()

    /**
     * Normalizes "orderBy" definition.
     */
    protected function normalizeOrderBy() {}

    // }}}
    // {{{ checkHavingSinglePrimaryKey()

    /**
     * Returns whether it checks that whether an associated table has a single
     * primary key.
     *
     * @return boolean
     */
    abstract protected function checkHavingSinglePrimaryKey();

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
