<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
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
 * @since      File available since Release 0.1.0
 */

// {{{ Piece_ORM_Config

/**
 * The configuration container for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Config
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_configurations = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ getDSN()

    /**
     * Gets the DSN for the given database.
     *
     * @param string $database
     * @return mixed
     */
    function getDSN($database)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (!$this->checkDatabase($database)) {
            return;
        }

        if (!array_key_exists('dsn', $this->_configurations[$database])) {
            return;
        }

        return $this->_configurations[$database]['dsn'];
    }

    // }}}
    // {{{ getOptions()

    /**
     * Gets the options for the given database.
     *
     * @param string $database
     * @return array
     */
    function getOptions($database)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (!$this->checkDatabase($database)) {
            return;
        }

        if (!array_key_exists('options', $this->_configurations[$database])) {
            return;
        }

        return $this->_configurations[$database]['options'];
    }

    // }}}
    // {{{ getConfigurations()

    /**
     * Gets the array of the configurations.
     *
     * @return array
     */
    function getConfigurations()
    {
        return $this->_configurations;
    }

    // }}}
    // {{{ merge()

    /**
     * Merges the given configuretion into the existing configuration.
     *
     * @param Piece_ORM_Config &$config
     */
    function merge(&$config)
    {
        $configurations = $config->getConfigurations();
        array_walk($configurations, array(&$this, 'mergeConfigurations'));
    }

    // }}}
    // {{{ mergeConfigurations()

    /**
     * A callback that will be called by array_walk() function in merge().
     *
     * @param array $configuration
     * @param string $database
     */
    function mergeConfigurations($configuration, $database)
    {
        $this->addConfiguration($database, $configuration['dsn'], $configuration['options']);
    }

    // }}}
    // {{{ getDefaultDatabase()

    /**
     * Gets the default database.
     *
     * @return string
     */
    function getDefaultDatabase()
    {
        return key($this->_configurations);
    }

    // }}}
    // {{{ getDirectorySuffix()

    /**
     * Gets the directory suffix for the given database.
     *
     * @param string $database
     * @return string
     */
    function getDirectorySuffix($database)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (!$this->checkDatabase($database)) {
            return;
        }

        if (!array_key_exists('directorySuffix', $this->_configurations[$database])) {
            return;
        }

        return $this->_configurations[$database]['directorySuffix'];
    }

    // }}}
    // {{{ setDSN()

    /**
     * Sets the DSN for a given database.
     *
     * @param string $database
     * @param mixed  $dsn
     */
    function setDSN($database, $dsn)
    {
        $this->_configurations[$database]['dsn'] = $dsn;
    }

    // }}}
    // {{{ setOptions()

    /**
     * Sets the options for a given database.
     *
     * @param string $database
     * @param string $options
     */
    function setOptions($database, $options)
    {
        $this->_configurations[$database]['options'] = $options;
    }

    // }}}
    // {{{ setDirectorySuffix()

    /**
     * Sets the directory suffix for a given database.
     *
     * @param string $database
     * @param string $directorySuffix
     */
    function setDirectorySuffix($database, $directorySuffix)
    {
        $this->_configurations[$database]['directorySuffix'] = $directorySuffix;
    }

    // }}}
    // {{{ checkDatabase()

    /**
     * Returns whether the given database exists in the current configuration
     * or not.
     *
     * @param string $database
     * @return boolean
     */
    function checkDatabase($database)
    {
        return array_key_exists($database, $this->_configurations);
    }

    // }}}
    // {{{ setUseMapperNameAsTableName()

    /**
     * Sets the useMapperNameAsTableName option value for the given database.
     *
     * @param string  $database
     * @param boolean $useMapperNameAsTableName
     * @since Method available since Release 1.0.0
     */
    function setUseMapperNameAsTableName($database, $useMapperNameAsTableName)
    {
        $this->_configurations[$database]['useMapperNameAsTableName'] = $useMapperNameAsTableName;
    }

    // }}}
    // {{{ getUseMapperNameAsTableName()

    /**
     * Gets the useMapperNameAsTableName option value for the given database.
     *
     * @param string $database
     * @return boolean
     * @since Method available since Release 1.0.0
     */
    function getUseMapperNameAsTableName($database)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (!$this->checkDatabase($database)) {
            return;
        }

        if (!array_key_exists('useMapperNameAsTableName', $this->_configurations[$database])) {
            return false;
        }

        return $this->_configurations[$database]['useMapperNameAsTableName'];
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
