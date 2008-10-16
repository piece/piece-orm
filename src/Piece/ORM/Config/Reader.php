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
 * @since      File available since Release 0.1.0
 */

namespace Piece::ORM::Config;

use Piece::ORM::Config;
use Piece::ORM::Exception;
use Piece::ORM::Env;
use Piece::ORM::Context::Registry;

require_once 'spyc.php5';

// {{{ Piece::ORM::Config::Reader

/**
 * A configuration reader for the Piece_ORM configuration DSL.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Reader
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

    private $_configDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets a directory where a configuration file exists to a property.
     *
     * @param string $configDirectory
     */
    public function __construct($configDirectory = null)
    {
        $this->_configDirectory = $configDirectory;
    }

    // }}}
    // {{{ read()

    /**
     * Creates a Piece::ORM::Config object from a configuration file or a cache.
     *
     * @return Piece::ORM::Config
     * @throws Piece::ORM::Exception
     */
    public function read()
    {
        if (is_null($this->_configDirectory)) {
            return new Config();
        }

        if (!file_exists($this->_configDirectory)) {
            throw new Exception("The configuration directory [ {$this->_configDirectory} ] is not found.");
        }

        $dslFile = "{$this->_configDirectory}/piece-orm-config.yaml";
        if (!file_exists($dslFile)) {
            throw new Exception("The configuration file [ $dslFile ] is not found.");
        }

        if (!is_readable($dslFile)) {
            throw new Exception("The configuration file [ $dslFile ] is not readable.");
        }

        if (is_null(Registry::getContext()->getCacheDirectory())) {
            return $this->_createConfigurationFromFile($dslFile);
        }

        if (!file_exists(Registry::getContext()->getCacheDirectory())) {
            trigger_error('The cache directory [ ' .
                          Registry::getContext()->getCacheDirectory() .
                          ' ] is not found.',
                          E_USER_WARNING
                          );
            return $this->_createConfigurationFromFile($dslFile);
        }

        if (!is_readable(Registry::getContext()->getCacheDirectory())
            || !is_writable(Registry::getContext()->getCacheDirectory())) {
            trigger_error('The cache directory [ ' .
                          Registry::getContext()->getCacheDirectory() .
                          ' ] is not readable or writable.',
                          E_USER_WARNING
                          );
            return $this->_createConfigurationFromFile($dslFile);
        }

        return $this->_getConfiguration($dslFile);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece::ORM::Config object from a cache.
     *
     * @param string $dslFile
     * @return Piece::ORM::Config
     */
    private function _getConfiguration($dslFile)
    {
        $dslFile = realpath($dslFile);
        $cache = new ::Cache_Lite_File(array('cacheDir' => Registry::getContext()->getCacheDirectory() . '/',
                                             'masterFile' => $dslFile,
                                             'automaticSerialization' => true,
                                             'errorHandlingAPIBreak' => true)
                                       );

        if (!Env::isProduction()) {
            $cache->remove($dslFile);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling ::PEAR::raiseError in default.
         */
        $config = $cache->get($dslFile);
        if (::PEAR::isError($config)) {
            trigger_error('Cannot read the cache file in the directory [ ' .
                          Registry::getContext()->getCacheDirectory() .
                          ' ].',
                          E_USER_WARNING
                          );
            return $this->_createConfigurationFromFile($dslFile);
        }

        if (!$config) {
            $config = $this->_createConfigurationFromFile($dslFile);
            $result = $cache->save($config);
            if (::PEAR::isError($result)) {
                trigger_error('Cannot write the Piece::ORM::Config object to the cache file in the directory [ ',
                              Registry::getContext()->getCacheDirectory() .
                              ' ].',
                              E_USER_WARNING
                              );
            }
        }

        return $config;
    }

    // }}}
    // {{{ _createConfigurationFromFile()

    /**
     * Parses the given file and returns a Piece::ORM::Config object.
     *
     * @param string $dslFile
     * @return Piece::ORM::Config
     */
    private function _createConfigurationFromFile($dslFile)
    {
        $config = new Config();
        $dsl = ::Spyc::YAMLLoad($dslFile);
        if (!is_array($dsl)) {
            return $config;
        }

        if (!array_key_exists('databases', $dsl)) {
            return $config;
        }

        foreach ($dsl['databases'] as $database => $configuration) {
            if (!array_key_exists('dsn', $configuration)) {
                throw new Exception("The element [ dsn ] is required in [ $dslFile ].");
            }

            if (!is_array($configuration['dsn']) && !strlen($configuration['dsn'])) {
                throw new Exception("The value of the element [ dsn ] is required in [ $dslFile ].");
            }

            $config->setDSN($database, $configuration['dsn']);

            if (array_key_exists('options', $configuration)) {
                if (!is_array($configuration['options'])) {
                    throw new Exception("The value of the element [ options ] must be an array in [ $dslFile ].");
                }

                $config->setOptions($database, $configuration['options']);
            }

            if (array_key_exists('directorySuffix', $configuration)) {
                if (!strlen($configuration['directorySuffix'])) {
                    throw new Exception("The value of the element [ directorySuffix ] is required in [ $dslFile ].");
                }

                $config->setDirectorySuffix($database, @$configuration['directorySuffix']);
            }

            if (array_key_exists('useMapperNameAsTableName', $configuration)) {
                if (!is_bool($configuration['useMapperNameAsTableName'])) {
                    throw new Exception("The value of the element [ useMapperNameAsTableName ] must be a boolean in [ $dslFile ].");
                }

                $config->setUseMapperNameAsTableName($database, $configuration['useMapperNameAsTableName']);
            }
        }

        return $config;
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
