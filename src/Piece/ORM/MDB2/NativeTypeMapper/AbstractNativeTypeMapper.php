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
 * @since      File available since Release 0.7.0
 */

namespace Piece::ORM::MDB2::NativeTypeMapper;

use Piece::ORM::Context::Registry;

// {{{ Piece::ORM::MDB2::NativeTypeMapper::AbstractNativeTypeMapper

/**
 * A helper class to map native datatypes of the DBMS to MDB2 datatypes.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.7.0
 */
abstract class AbstractNativeTypeMapper
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

    private $_driverName;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets the driver name to the property.
     */
    public function __construct()
    {
        $this->_driverName = strtolower(substr(strrchr(get_class($this), ':'), 1));
        if (!Registry::getContext()->hasAttribute(__CLASS__ . '::nativeTypeMap')) {
            $this->initialize();
        }
    }

    // }}}
    // {{{ mapNativeType()

    /**
     * Maps a native datatype of the DBMS to a MDB2 datatype.
     *
     * @param ::MDB2_Driver_Common $dbh
     */
    public function mapNativeType(::MDB2_Driver_Common $dbh)
    {
        $nativeTypeMap = self::_getNativeTypeMap();
         if (!array_key_exists($this->_driverName, $nativeTypeMap)) {
            return;
        }

        $callbacks = array();
        foreach (array_keys($nativeTypeMap[ $this->_driverName ]) as $type) {
            $callbacks[$type] = array($this, 'getMDB2TypeInfo');
        }

        $dbh->setOption('nativetype_map_callback', $callbacks);
    }

    // }}}
    // {{{ getMDB2TypeInfo()

    /**
     * Gets the MDB2 datatype information of a native array description of a field.
     *
     * @param ::MDB2_Driver_Common $dbh
     * @param array              $field
     * @return array
     */
    public function getMDB2TypeInfo(::MDB2_Driver_Common $dbh, array $field)
    {
        $nativeTypeMap = self::_getNativeTypeMap();
        return array(array($nativeTypeMap[ $this->_driverName ][ $field['type'] ]),
                     null,
                     null,
                     null
                     );
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ addMapForDriver()

    /**
     * Adds an element to the map for a given driver.
     *
     * @param string $nativeType
     * @param string $mdb2Type
     * @param string $driverName
     */
    protected static function addMapForDriver($nativeType, $mdb2Type, $driverName)
    {
        $nativeTypeMap = self::_getNativeTypeMap();
        $nativeTypeMap[$driverName][$nativeType] = $mdb2Type;
        self::_setNativeTypeMap($nativeTypeMap);
    }

    // }}}
    // {{{ initialize()

    /**
     * Initializes the map for the current driver.
     */
    protected function initialize() {}

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _getNativeTypeMap()

    /**
     * Gets the native type map from the current context.
     *
     * @return array
     */
    private static function _getNativeTypeMap()
    {
        if (!Registry::getContext()->hasAttribute(__CLASS__ . '::nativeTypeMap')) {
            return array();
        }

        return Registry::getContext()->getAttribute(__CLASS__ . '::nativeTypeMap');
    }

    // }}}
    // {{{ _setNativeTypeMap()

    /**
     * Sets the metadata registry to the current context.
     *
     * @param array $nativeTypeMap
     */
    private static function _setNativeTypeMap(array $nativeTypeMap)
    {
        Registry::getContext()->setAttribute(__CLASS__ . '::nativeTypeMap', $nativeTypeMap);
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
