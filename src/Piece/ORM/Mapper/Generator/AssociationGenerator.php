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

namespace Piece::ORM::Mapper::Generator;

use Piece::ORM::Mapper::AssociationType;
use Piece::ORM::Exception;

// {{{ Piece::ORM::Mapper::Generator::AssociationGenerator

/**
 * A generator which generates an association property dceclaration.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class AssociationGenerator
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

    private $_propertyName;
    private $_associations;
    private $_metadata;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes the properties with the arguments.
     *
     * @param string               $propertyName
     * @param array                $associations
     * @param Piece::ORM::Metadata $metadata
     */
    public function __construct($propertyName, $associations, $metadata)
    {
        $this->_propertyName = $propertyName;
        $this->_associations = $associations;
        $this->_metadata = $metadata;
    }

    // }}}
    // {{{ generate()

    /**
     * Generates an association property dceclaration.
     *
     * @return string
     */
    public function generate()
    {
        $normalizedAssociations = array();
        foreach ($this->_associations as $mappedAs => $definition) {
            if (!array_key_exists('type', $definition)) {
                throw new Exception('The element [ type ] is required to generate an association property declaration.');
            }

            if (!AssociationType::isValid($definition['type'])) {
                throw new Exception('The value of the element [ type ] must be one of ' . implode(', ', AssociationType::getAssociationTypes()));
            }

            $class = __NAMESPACE__ .
                '::AssociationNormalizerStrategy::' .
                ucwords($definition['type']);
            $associationNormalizer = new $class($definition, $this->_metadata);
            $normalizedAssociations[$mappedAs] = $associationNormalizer->normalize();
        }

        return "    public \$__association__{$this->_propertyName} = " .
            var_export($normalizedAssociations, true) .
            ';';
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
