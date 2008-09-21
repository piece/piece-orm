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

// {{{ Piece_ORM_Mapper_RelationshipNormalizer_ManyToMany

/**
 * An relationship normalizer for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_RelationshipNormalizer_ManyToMany extends Piece_ORM_Mapper_RelationshipNormalizer_Common
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
     * @throws Piece_ORM_Exception
     */
    protected function normalizeThrough()
    {
        if (!array_key_exists('through', $this->relationship)) {
            $this->relationship['through'] = array();
        }

        if (!array_key_exists('table', $this->relationship['through'])) {
            $throughTableName1 = $this->metadata->getTableName(true) . "_{$this->relationship['table']}";
            $throughTableName2 = "{$this->relationship['table']}_" . $this->metadata->getTableName(true);
            foreach (array($throughTableName1, $throughTableName2) as $throughTableName) {
                try {
                    $throughMetadata = Piece_ORM_Metadata_Factory::factory($throughTableName);
                } catch (Piece_ORM_Metadata_Factory_NoSuchTableException $e) {
                    continue;
                } catch (Piece_ORM_Exception $e) {
                    throw $e;
                }

                $this->relationship['through']['table'] = $throughTableName;
                break;
            }

            if (!$throughMetadata) {
                throw new Piece_ORM_Exception("One of [ $throughTableName1 ] or [ $throughTableName2 ] must exists in the database, if the element [ table ] in the element [ through ] omit.");
            }
        }

        $throughMetadata = Piece_ORM_Metadata_Factory::factory($this->relationship['through']['table']);

        if (!array_key_exists('column', $this->relationship['through'])) {
            $primaryKey = $this->metadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Piece_ORM_Exception('A single primary key field is required, if the element [ column ] in the element [ through ] omit.');
            }
                
            $this->relationship['through']['column'] = $this->metadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$throughMetadata->hasField($this->relationship['through']['column'])) {
            throw new Piece_ORM_Exception("The field [ {$this->relationship['through']['column']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('referencedColumn', $this->relationship['through'])) {
            $primaryKey = $this->metadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Piece_ORM_Exception('A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.');
            }

            $this->relationship['through']['referencedColumn'] = $primaryKey;
        } 

        if (!$this->metadata->hasField($this->relationship['through']['referencedColumn'])) {
            throw new Piece_ORM_Exception("The field [ {$this->relationship['through']['referencedColumn']} ] not found in the table [ " . $this->metadata->getTableName(true) . ' ].');
        }

        if (!array_key_exists('inverseColumn', $this->relationship['through'])) {
            $primaryKey = $this->relationshipMetadata->getPrimaryKey();
            if (is_null($primaryKey)) {
                throw new Piece_ORM_Exception('A single primary key field is required, if the element [ column ] in the element [ through ] omit.');
            }

            $this->relationship['through']['inverseColumn'] = $this->relationshipMetadata->getTableName(true) . "_$primaryKey";
        } 

        if (!$throughMetadata->hasField($this->relationship['through']['inverseColumn'])) {
            throw new Piece_ORM_Exception("The field [ {$this->relationship['through']['inverseColumn']} ] not found in the table [ " . $throughMetadata->getTableName(true) . ' ].');
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
        $primaryKey = $this->relationshipMetadata->getPrimaryKey();
        if (is_null($primaryKey)) {
            return false;
        }

        $this->relationship['column'] = $primaryKey;
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
        $this->relationship['referencedColumn'] = null;
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
