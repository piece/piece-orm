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

require_once 'Piece/ORM/Config/Factory.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Metadata/Factory.php';
require_once 'Piece/ORM/Context.php';

// {{{ Piece_ORM

/**
 * A single entry point for Piece_ORM mappers.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM
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
     * @static
     */

    // }}}
    // {{{ configure()

    /**
     * Configures the Piece_ORM environment.
     *
     * First this method tries to load a configuration from a configuration
     * file in the given configration directory using
     * Piece_ORM_Config_Factory::factory(). The method creates a new object
     * if the load failed.
     * Second this method merges the given configuretion into the loaded
     * configuration.
     * Finally this method sets the configuration to the current context.
     * And also this method sets a configuration/cache directory for mappers
     * and a cache directory for Piece_ORM_Metadata class.
     *
     * @param string           $configDirectory
     * @param string           $cacheDirectory
     * @param Piece_ORM_Config $dynamicConfig
     * @param string           $mapperConfigDirectory
     * @param string           $mapperCacheDirectory
     * @param string           $metadataCacheDirectory
     */
    function configure($configDirectory,
                       $cacheDirectory,
                       $dynamicConfig,
                       $mapperConfigDirectory,
                       $mapperCacheDirectory,
                       $metadataCacheDirectory = null
                       )
    {
        $config = &Piece_ORM_Config_Factory::factory($configDirectory, $cacheDirectory);

        if (strtolower(get_class($dynamicConfig)) == strtolower('Piece_ORM_Config')) {
            $config->merge($dynamicConfig);
        }

        $context = &Piece_ORM_Context::singleton();
        $context->setConfiguration($config);

        Piece_ORM_Mapper_Factory::setConfigDirectory($mapperConfigDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($mapperCacheDirectory);
        Piece_ORM_Metadata_Factory::setCacheDirectory($metadataCacheDirectory);
    }

    // }}}
    // {{{ getMapper()

    /**
     * Gets a mapper object for a given mapper name.
     *
     * @param string $mapperName
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &getMapper($mapperName)
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory($mapperName);
        return $mapper;
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
