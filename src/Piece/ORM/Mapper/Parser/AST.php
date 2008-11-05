<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 2.0.0dev1
 */

namespace Piece::ORM::Mapper::Parser;

use Piece::ORM::Exception;

// {{{ Piece::ORM::Mapper::Parser::AST

/**
 * The class representing abstract syntax tree for the Mapper DSL.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class AST extends DOMDocument
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ addMethod()

    /**
     * Adds a method element.
     *
     * @param string $name
     * @param string $query
     * @param string $orderBy
     * @param array  $associations
     */
    public function addMethod($name, $query = null, $orderBy = null, $associations = null)
    {
        $id = strtolower($name);
        $xpath = new DOMXPath($this);
        $methodNodeList = $xpath->query("//method[@id='$id']");
        if (!$methodNodeList->length) {
            $methodElement = $this->appendChild(new DOMElement('method'));
            $methodElement->setAttribute('id', $id);
            $methodElement->setAttribute('name', $name);
        } else {
            $methodElement = $methodNodeList->item(0);
        }

        if (!is_null($query)) {
            $methodElement->setAttribute('query', $query);
        }

        $methodElement->setAttribute('orderBy', $orderBy);

        if (!is_null($associations)) {
            foreach ($associations as $associationElement) {
                if ($associationElement->hasAttribute('referencedAssociationID')) {
                    $referencedAssociationID = $associationElement->getAttribute('referencedAssociationID');
                    $referencedAssociation = $associationElement->getAttribute('referencedAssociation');
                    $associationNodeList = $xpath->query("//method[@id='$id']/association[@referencedAssociationID='$referencedAssociationID']");
                    if ($associationNodeList->length) {
                        throw new Exception("Cannot redeclare the association reference [ $referencedAssociation ] in the method statement");
                    }
                }

                $methodElement->appendChild($associationElement);
            }
        }
    }

    // }}}
    // {{{ createAssociation()

    /**
     * Creates a DOMElement object representing an association element.
     *
     * @param array $associations
     * @return DOMElement
     * @throws Piece::ORM::Exception
     */
    public function createAssociation($associations)
    {
        $requiredKeys = array('table', 'type', 'property');
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $associations)) {
                throw new Exception("The [ $requiredKey ] statement was not found in the association statement. An association statement must contain the table, type, and property statements.");
            }
        }

        $associationElement = $this->createElement('association');

        foreach (array_keys((array)$associations) as $key) {
            if ($key == 'linkTable') {
                $associationElement->appendChild($associations[$key]);
                continue;
            }

            $associationElement->setAttribute($key, $associations[$key]);
        }

        return $associationElement;
    }

    // }}}
    // {{{ addAssociation()

    /**
     * Adds an association element.
     *
     * @param string $name
     * @param array  $associations
     */
    public function addAssociation($name, array $associations)
    {
        $id = strtolower($name);
        $xpath = new DOMXPath($this);
        $associationNodeList = $xpath->query("//association[@id='$id']");
        if ($associationNodeList->length) {
            return;
        }

        $associationElement = $this->createAssociation($associations);
        $associationElement->setAttribute('id', $id);
        $associationElement->setAttribute('name', $name);

        $this->appendChild($associationElement);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

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
