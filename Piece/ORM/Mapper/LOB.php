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
 * @since      File available since Release 1.0.0
 */

namespace Piece::ORM::Mapper;

use Piece::ORM::Metadata;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Exception;

// {{{ Piece::ORM::Mapper::LOB

/**
 * The LOB representation class.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class LOB
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

    private $_fieldName;
    private $_source;
    private $_value;
    private $_dbh;
    private $_metadata;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets a database handle and a LOB source.
     *
     * @param ::MDB2_Driver_Common $dbh
     * @param Piece::ORM::Metadata $metadata
     * @param string|resource      $source
     */
    public function __construct(::MDB2_Driver_Common $dbh,
                                Metadata $metadata,
                                $source
                                )
    {
        $this->_dbh = $dbh;
        $this->_metadata = $metadata;

        if (!is_null($source)) {
            $this->_source = $source;
        }
    }

    // }}}
    // {{{ setFieldName()

    /**
     * Sets the field name which is a LOB field.
     *
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->_fieldName = $fieldName;
    }

    // }}}
    // {{{ setValue()

    /**
     * Sets the escaped value of this field.
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    // }}}
    // {{{ getSource()

    /**
     * Gets the LOB source for this field.
     *
     * @return string|resource
     */
    public function getSource()
    {
        return $this->_source;
    }

    // }}}
    // {{{ load()

    /**
     * Loads the LOB data of this field.
     *
     * @return string
     * @throws Piece::ORM::Exception::PEARException
     * @throws Piece::ORM::Exception
     */
    public function load()
    {
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $datatype = $this->_dbh->loadModule('Datatype');
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($datatype)) {
            throw new PEARException($datatype);
        }

        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $lob = $datatype->convertResult($this->_value,
                                        $this->_metadata->getDatatype($this->_fieldName)
                                        );
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($lob)) {
            throw new PEARException($lob);
        }

        if (!is_resource($lob)) {
            throw new Exception('An unexpected value detected. $datatype->convertResult() should return a resource.');
        }

        $data = '';
        while (!feof($lob)) {
            $data .= fread($lob, 8192);
        }

        $datatype->destroyLOB($lob);

        return $data;
    }

    // }}}
    // {{{ setSource()

    /**
     * Sets a LOB source for this field.
     *
     * @param string|resource $source
     */
    public function setSource($source)
    {
        $this->_source = $source;
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
