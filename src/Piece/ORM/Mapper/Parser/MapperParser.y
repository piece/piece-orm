%name MapperParser

%declare_class {
// {{{ Piece::ORM::Mapper::Parser::MapperParser

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class MapperParser
}

%include {
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

namespace Piece::ORM::Mapper::Parser;

use Piece::ORM::Exception;
use Piece::ORM::Mapper::Parser::MapperLexer;
use Piece::ORM::Mapper::AST;
}

%syntax_error {
    echo "Syntax Error on line {$this->_mapperLexer->line}: token '{$this->_mapperLexer->value}' while parsing rule:";

    foreach ($this->yystack as $entry) {
        echo $this->tokenName($entry->major) . ' ';
    }

    $expectedTokens = array();
    foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
        $expectedTokens[] = self::$yyTokenName[$token];
    }

    throw new Exception('Unexpected ' . $this->tokenName($yymajor) .
                        "($TOKEN), expected one of: " .
                        implode(',', $expectedTokens)
                        );
}

%include_class {
    private $_mapperLexer;
    private $_ast;

    public function __construct(MapperLexer $mapperLexer, AST $ast)
    {
        $this->_mapperLexer = $mapperLexer;
        $this->_ast = $ast;
    }
}

mapper ::= method_declaration_statement_list.

method_declaration_statement_list ::= method_declaration_statement_list method_declaration_statement.
method_declaration_statement_list ::= .

method_declaration_statement ::=
        METHOD ID(A) LCURLY
            query_declaration_statement(B)
            orderby_declaration_statement(C)
        RCURLY. {
        $this->_ast->addMethod(A, trim(B, '"'), trim(C, '"'));
}

query_declaration_statement(A) ::= QUERY STRING(B). {
       A = B;
}
query_declaration_statement ::= .

orderby_declaration_statement(A) ::= ORDERBY STRING(B). {
       A = B;
}
orderby_declaration_statement ::= .
