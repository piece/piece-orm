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

namespace Piece::ORM::Mapper::Generator::AssociationGeneratorStrategy;

use Piece::ORM::Mapper::Generator::AssociationGeneratorStrategy::AbstractAssociationGenerator;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Metadata::MetadataFactory::NoSuchTableException;
use Piece::ORM::Exception;

// {{{ Piece::ORM::Mapper::Generator::AssociationGeneratorStrategy::ManyToMany

/**
 * A generator for Many-to-Many associations.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ManyToMany extends AbstractAssociationGenerator
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $referencedColumnRequired = false;

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ normalizeThrough()

    /**
     * Normalizes "through" definition.
     *
     * @throws Piece::ORM::Exception
     */
    protected function normalizeThrough()
    {
        if (!array_key_exists('through', $this->association)) {
            $this->association['through'] = array();
        }

        if (!array_key_exists('table', $this->association['through'])) {
            $throughTableName1 = $this->metadata->getTableName(true) . "_{$this->association['table']}";
            $throughTableName2 = "{$this->association['table']}_" . $this->metadata->getTableName(true);
            foreach (array($throughTableName1, $throughTableName2) as $throughTableName) {
                try {
                    $throughMetadata = MetadataFactory::factory($throughTableName);
                } catch (NoSuchTableException $e) {
                    continue;
                } catch (Exception $e) {
                    throw $e;
                }

                $this->association['through']['table'] = $throughTableName;
                break;
            }

            if (!$throughMetadata) {
                throw new Exception("One of [ $throughTableName1 ] or [ $throughTableName2 ] must exists in the database, if the element [ table ] in the element [ through ] omit.");
            }
        }

        $throughMetadata = MetadataFactory::factory($this->association['through']['table']);

        if (!array_key_exists('column', $this->association['through'])) {
            $primaryKey = $this->metadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Exception('A single primary key field is required, if the element [ column ] in the element [ through ] omit.');
            }
                
            $this->association['through']['column'] = $this->metadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$throughMetadata->hasField($this->association['through']['column'])) {
            throw new Exception("The field [ {$this->association['through']['column']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('referencedColumn', $this->association['through'])) {
            $primaryKey = $this->metadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Exception('A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.');
            }

            $this->association['through']['referencedColumn'] = $primaryKey;
        } 

        if (!$this->metadata->hasField($this->association['through']['referencedColumn'])) {
            throw new Exception("The field [ {$this->association['through']['referencedColumn']} ] not found in the table [ " . $this->metadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('inverseColumn', $this->association['through'])) {
            $primaryKey = $this->associationMetadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Exception('A single primary key field is required, if the element [ column ] in the element [ through ] omit.');
            }

            $this->association['through']['inverseColumn'] = $this->associationMetadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$throughMetadata->hasField($this->association['through']['inverseColumn'])) {
            throw new Exception("The field [ {$this->association['through']['inverseColumn']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].');
        }
    }

    // }}}
    // {{{ normalizeColumn()

    /**
     * Normalizes "column" definition.
     *
     * @return boolean
     */
    protected function normalizeColumn()
    {
        $primaryKey = $this->associationMetadata->getPrimaryKey();
        if (is_null($primaryKey)) {
            return false;
        }

        $this->association['column'] = $primaryKey;
        return true;
    }

    // }}}
    // {{{ normalizeReferencedColumn()

    /**
     * Normalizes "referencedColumn" definition.
     *
     * @return boolean
     */
    protected function normalizeReferencedColumn()
    {
        $this->association['referencedColumn'] = null;
        return true;
    }

    // }}}
    // {{{ checkHavingSinglePrimaryKey()

    /**
     * Returns whether it checks that whether an associated table has a single
     * primary key.
     *
     * @return boolean
     */
    protected function checkHavingSinglePrimaryKey()
    {
        return true;
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
