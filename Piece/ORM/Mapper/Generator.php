<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Inflector.php';

// {{{ Piece_ORM_Mapper_Generator

/**
 * The source code generator which generates a mapper source based on
 * a given configuration.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_Generator
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_mapperClass;
    var $_mapperName;
    var $_config;
    var $_metadata;
    var $_methodDefinitions = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes the properties with the arguments.
     *
     * @param string             $mapperClass
     * @param string             $mapperName
     * @param array              $config
     * @param Piece_ORM_Metadata &$metadata
     */
    function Piece_ORM_Mapper_Generator($mapperClass, $mapperName, $config, &$metadata)
    {
        $this->_mapperClass = $mapperClass;
        $this->_mapperName  = $mapperName;
        $this->_config      = $config;
        $this->_metadata    = &$metadata;
    }

    // }}}
    // {{{ generate()

    /**
     * Generates a mapper source.
     *
     * @return string
     */
    function generate()
    {
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $datatype = $this->_metadata->getDatatype($fieldName);
            if ($datatype == 'integer' || $datatype == 'text') {
                
                $camelizedFieldName = Piece_ORM_Inflector::camelize($fieldName);
                $methodName = "findBy$camelizedFieldName";
                $this->_addFind($methodName, 'SELECT * FROM ' . $this->_metadata->getTableName() . " WHERE $fieldName = \$" . Piece_ORM_Inflector::lowerCaseFirstLetter($camelizedFieldName));
            }
        }

        foreach ($this->_config as $method) {
            if (substr($method['name'], 0, 6) == 'findBy') {
                $this->_addFind($method['name'], $method['query']);
            } elseif (substr($method['name'], 0, 6) == 'insert') {
                $this->_addInsert($method['query']);
            }
        }

        return "class {$this->_mapperClass} extends Piece_ORM_Mapper_Common
{" . implode("\n", $this->_methodDefinitions) . "\n}";
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _addFind()

    /**
     * Adds a findByXXX method and its query to the mapper source.
     *
     * @param string $methodName
     * @param string $query
     */
    function _addFind($methodName, $query)
    {
        $propertyName = strtolower($methodName);
        $this->_methodDefinitions[$methodName] = "
    var \${$propertyName} = '$query';
    function &$methodName(\$criteria)
    {
        \$object = &\$this->_find(__FUNCTION__, \$criteria);
        return \$object;
    }";
    }

    // }}}
    // {{{ _addInsert()

    /**
     * Adds the insert method and its query to the mapper source.
     *
     * @param string $query
     */
    function _addInsert($query)
    {
        $this->_methodDefinitions['insert'] = "
    var \$insert = '$query';";
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
?>
