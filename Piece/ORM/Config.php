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

// {{{ Piece_ORM_Config

/**
 * The configuration container for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
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
     * Gets the DSN for the given configuration name.
     *
     * @param string $configurationName
     * @return string
     */
    function getDSN($configurationName = null)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (is_null($configurationName)) {
            $configurationName = key($this->_configurations);
        }

        if (!array_key_exists($configurationName, $this->_configurations)) {
            return;
        }

        return $this->_configurations[$configurationName]['dsn'];
    }

    // }}}
    // {{{ getOptions()

    /**
     * Gets the options for the given configuration name.
     *
     * @param string $configurationName
     * @return array
     */
    function getOptions($configurationName = null)
    {
        if (!count($this->_configurations)) {
            return;
        }

        if (is_null($configurationName)) {
            $configurationName = key($this->_configurations);
        }

        if (!array_key_exists($configurationName, $this->_configurations)) {
            return;
        }

        return $this->_configurations[$configurationName]['options'];
    }

    // }}}
    // {{{ addConfiguration()

    /**
     * Adds a database configuration.
     *
     * @param string $configurationName
     * @param string $dsn
     * @param array  $options
     */
    function addConfiguration($configurationName, $dsn, $options = false)
    {
        $this->_configurations[$configurationName] = array('dsn' => $dsn,
                                                           'options' => $options
                                                           );
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
     * @param string $configurationName
     */
    function mergeConfigurations($configuration, $configurationName)
    {
        $this->addConfiguration($configurationName, $configuration['dsn'], $configuration['options']);
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
