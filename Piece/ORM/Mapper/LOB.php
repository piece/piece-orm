<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
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

// {{{ Piece_ORM_Mapper_LOB

/**
 * The LOB representation class.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class Piece_ORM_Mapper_LOB
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_fieldName;
    var $_source;
    var $_escapedValue;
    var $_dbh;
    var $_value;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets a database handle and a LOB source.
     *
     * @param MDB2_Driver_Common &$dbh
     * @param string|resource    $source
     */
    function Piece_ORM_Mapper_LOB(&$dbh, $source)
    {
        $this->_dbh = &$dbh;

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
    function setFieldName($fieldName)
    {
        $this->_fieldName = $fieldName;
    }

    // }}}
    // {{{ setEscapedValue()

    /**
     * Sets the escaped value of this field.
     *
     * @param string $escapedValue
     */
    function setEscapedValue($escapedValue)
    {
        $this->_escapedValue = $escapedValue;
    }

    // }}}
    // {{{ getSource()

    /**
     * Gets the LOB source for this field.
     *
     * @return string|resource
     */
    function getSource()
    {
        return $this->_source;
    }

    // }}}
    // {{{ getValue()

    /**
     * Gets the unescaped value of this field.
     *
     * @return string
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     */
    function getValue()
    {
        if (is_null($this->_value)) {
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $datatype = &$this->_dbh->loadModule('Datatype');
            PEAR::staticPopErrorHandling();
            if (MDB2::isError($datatype)) {
                Piece_ORM_Error::pushPEARError($datatype,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke $dbh->loadModule() for any reasons.'
                                               );
                return;
            }

            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $lob = $datatype->convertResult($this->_escapedValue, 'blob');
            PEAR::staticPopErrorHandling();
            if (MDB2::isError($lob)) {
                Piece_ORM_Error::pushPEARError($lob,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke $datatype->convertResult() for any reasons.'
                                               );
                return;
            }

            if (!is_resource($lob)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. $datatype->convertResult() should return a resource.'
                                      );
                return;
            }

            $this->_value = '';
            while (!feof($lob)) {
                $this->_value .= fread($lob, 8192);
            }

            $datatype->destroyLOB($lob);
            $this->_escapedValue = null;
        }

        return $this->_value;
    }

    // }}}
    // {{{ setSource()

    /**
     * Sets a LOB source for this field.
     *
     * @param string $source
     */
    function setSource($source)
    {
        $this->_source = $source;
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
?>
