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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Config.php';
require_once 'Piece/ORM/Error.php';
require_once 'Cache/Lite/File.php';
require_once 'PEAR.php';

if (version_compare(phpversion(), '5.0.0', '<')) {
    require_once 'spyc.php';
} else {
    require_once 'spyc.php5';
}

// {{{ Piece_ORM_Config_Factory

/**
 * A factory class for creating Piece_ORM_Config objects.
 *
 * @package    Piece_ORM
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Config_Factory
{

    // {{{ properties

    /**#@+
     * @access public
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
    // {{{ factory()

    /**
     * Creates a Piece_ORM_Config object from a configuration file or
     * a cache.
     *
     * @param string $configDirectory
     * @param string $cacheDirectory
     * @return Piece_ORM_Config
     * @static
     */
    function &factory($configDirectory = null, $cacheDirectory = null)
    {
        if (is_null($configDirectory)) {
            $config = &new Piece_ORM_Config();
            return $config;
        }

        if (!file_exists($configDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The configuration directory [ $configDirectory ] not found."
                                  );
            $return = null;
            return $return;
        }

        $configFile = "$configDirectory/piece-orm-config.yaml";
        if (!file_exists($configFile)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The configuration file [ $configFile ] not found."
                                  );
            $return = null;
            return $return;
        }

        if (!is_readable($configFile)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_READABLE,
                                  "The configuration file [ $configFile ] is not readable."
                                  );
            $return = null;
            return $return;
        }

        if (is_null($cacheDirectory)) {
            $cacheDirectory = './cache';
        }

        if (!file_exists($cacheDirectory)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The cache directory [ $cacheDirectory ] not found.",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $config = &Piece_ORM_Config_Factory::_createConfigurationFromFile($configFile);
            return $config;
        }

        if (!is_readable($cacheDirectory) || !is_writable($cacheDirectory)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_READABLE,
                                  "The cache directory [ $cacheDirectory ] is not readable or writable.",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $config = &Piece_ORM_Config_Factory::_createConfigurationFromFile($configFile);
            return $config;
        }

        return Piece_ORM_Config_Factory::_getConfiguration($configFile, $cacheDirectory);
    }

    /**#@-*/

    /**#@+
     * @access private
     * @static
     */

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece_ORM_Config object from a cache.
     *
     * @param string $masterFile
     * @param string $cacheDirectory
     * @return Piece_ORM_Config
     */
    function &_getConfiguration($masterFile, $cacheDirectory)
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "$cacheDirectory/",
                                            'masterFile' => $masterFile,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $config = $cache->get($masterFile);
        if (PEAR::isError($config)) {
            Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_READ,
                                  "Cannot read the cache file in the directory [ $cacheDirectory ].",
                                  'warning'
                                  );
            Piece_ORM_Error::popCallback();

            $config = &Piece_ORM_Config_Factory::_createConfigurationFromFile($masterFile);
            return $config;
        }

        if (!$config) {
            $config = &Piece_ORM_Config_Factory::_createConfigurationFromFile($masterFile);
            $result = $cache->save($config);
            if (PEAR::isError($result)) {
                Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_WRITE,
                                      "Cannot write the Piece_ORM_Config object to the cache file in the directory [ $cacheDirectory ].",
                                      'warning'
                                      );
                Piece_ORM_Error::popCallback();
            }
        }

        return $config;
    }

    // }}}
    // {{{ _createConfigurationFromFile()

    /**
     * Parses the given file and returns a Piece_ORM_Config object.
     *
     * @param string $file
     * @return Piece_ORM_Config
     */
    function &_createConfigurationFromFile($file)
    {
        $config = &new Piece_ORM_Config();
        $yaml = Spyc::YAMLLoad($file);
        foreach ($yaml as $configuration) {
            $config->setDSN($configuration['name'], $configuration['dsn']);
            $config->setOptions($configuration['name'], @$configuration['options']);
            $config->setDirectorySuffix($configuration['name'], @$configuration['directorySuffix']);
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
?>
