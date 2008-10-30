<?php
/* Driver template for the PHP_MapperParserrGenerator parser generator. (PHP port of LEMON)
*/

namespace Piece::ORM::Mapper::Parser;

/**
 * This can be used to store both the string representation of
 * a token, and any useful meta-data associated with the token.
 *
 * meta-data should be stored as an array
 */
class MapperParseryyToken implements ArrayAccess
{
    public $string = '';
    public $metadata = array();

    function __construct($s, $m = array())
    {
        if ($s instanceof MapperParseryyToken) {
            $this->string = $s->string;
            $this->metadata = $s->metadata;
        } else {
            $this->string = (string) $s;
            if ($m instanceof MapperParseryyToken) {
                $this->metadata = $m->metadata;
            } elseif (is_array($m)) {
                $this->metadata = $m;
            }
        }
    }

    function __toString()
    {
        return $this->_string;
    }

    function offsetExists($offset)
    {
        return isset($this->metadata[$offset]);
    }

    function offsetGet($offset)
    {
        return $this->metadata[$offset];
    }

    function offsetSet($offset, $value)
    {
        if ($offset === null) {
            if (isset($value[0])) {
                $x = ($value instanceof MapperParseryyToken) ?
                    $value->metadata : $value;
                $this->metadata = array_merge($this->metadata, $x);
                return;
            }
            $offset = count($this->metadata);
        }
        if ($value === null) {
            return;
        }
        if ($value instanceof MapperParseryyToken) {
            if ($value->metadata) {
                $this->metadata[$offset] = $value->metadata;
            }
        } elseif ($value) {
            $this->metadata[$offset] = $value;
        }
    }

    function offsetUnset($offset)
    {
        unset($this->metadata[$offset]);
    }
}

/** The following structure represents a single element of the
 * parser's stack.  Information stored includes:
 *
 *   +  The state number for the parser at this level of the stack.
 *
 *   +  The value of the token stored at this level of the stack.
 *      (In other words, the "major" token.)
 *
 *   +  The semantic value stored at this level of the stack.  This is
 *      the information used by the action routines in the grammar.
 *      It is sometimes called the "minor" token.
 */
class MapperParseryyStackEntry
{
    public $stateno;       /* The state-number */
    public $major;         /* The major token value.  This is the code
                     ** number for the token at this stack level */
    public $minor; /* The user-supplied minor token value.  This
                     ** is the value of the token  */
};

// code external to the class is included here
#line 15 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

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
 * @version    SVN: $Id: MapperParser.y 590 2008-10-27 15:41:26Z iteman $
 * @since      File available since Release 2.0.0dev1
 */

use Piece::ORM::Exception;
use Piece::ORM::Mapper::Parser::MapperLexer;
use Piece::ORM::Mapper::Parser::AST;
#line 142 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

// declare_class is output here
#line 3 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

// {{{ Piece::ORM::Mapper::Parser::MapperParser

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class MapperParser
#line 158 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
{
/* First off, code is included which follows the "include_class" declaration
** in the input file. */
#line 75 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

    private $_mapperLexer;
    private $_ast;

    public function __construct(MapperLexer $mapperLexer, AST $ast)
    {
        $this->_mapperLexer = $mapperLexer;
        $this->_ast = $ast;
    }
#line 173 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

/* Next is all token values, as class constants
*/
/* 
** These constants (all generated automatically by the parser generator)
** specify the various kinds of tokens (terminals) that the parser
** understands. 
**
** Each symbol here is a terminal symbol in the grammar.
*/
    const METHOD                         =  1;
    const ID                             =  2;
    const LCURLY                         =  3;
    const RCURLY                         =  4;
    const QUERY                          =  5;
    const STRING                         =  6;
    const ORDER_BY                       =  7;
    const ASSOCIATION                    =  8;
    const THROUGH                        =  9;
    const TABLE                          = 10;
    const ASSOCIATION_TYPE               = 11;
    const PROPERTY                       = 12;
    const COLUMN                         = 13;
    const REFERENCED_COLUMN              = 14;
    const INVERSE_COLUMN                 = 15;
    const YY_NO_ACTION = 89;
    const YY_ACCEPT_ACTION = 88;
    const YY_ERROR_ACTION = 87;

/* Next are that tables used to determine what action to take based on the
** current state and lookahead token.  These tables are used to implement
** functions that take a state number and lookahead value and return an
** action integer.  
**
** Suppose the action integer is N.  Then the action is determined as
** follows
**
**   0 <= N < self::YYNSTATE                              Shift N.  That is,
**                                                        push the lookahead
**                                                        token onto the stack
**                                                        and goto state N.
**
**   self::YYNSTATE <= N < self::YYNSTATE+self::YYNRULE   Reduce by rule N-YYNSTATE.
**
**   N == self::YYNSTATE+self::YYNRULE                    A syntax error has occurred.
**
**   N == self::YYNSTATE+self::YYNRULE+1                  The parser accepts its
**                                                        input. (and concludes parsing)
**
**   N == self::YYNSTATE+self::YYNRULE+2                  No such action.  Denotes unused
**                                                        slots in the yy_action[] table.
**
** The action table is constructed as a single large static array $yy_action.
** Given state S and lookahead X, the action is computed as
**
**      self::$yy_action[self::$yy_shift_ofst[S] + X ]
**
** If the index value self::$yy_shift_ofst[S]+X is out of range or if the value
** self::$yy_lookahead[self::$yy_shift_ofst[S]+X] is not equal to X or if
** self::$yy_shift_ofst[S] is equal to self::YY_SHIFT_USE_DFLT, it means that
** the action is not in the table and that self::$yy_default[S] should be used instead.  
**
** The formula above is for computing the action when the lookahead is
** a terminal symbol.  If the lookahead is a non-terminal (as occurs after
** a reduce action) then the static $yy_reduce_ofst array is used in place of
** the static $yy_shift_ofst array and self::YY_REDUCE_USE_DFLT is used in place of
** self::YY_SHIFT_USE_DFLT.
**
** The following are the tables generated in this section:
**
**  self::$yy_action        A single table containing all actions.
**  self::$yy_lookahead     A table containing the lookahead for each entry in
**                          yy_action.  Used to detect hash collisions.
**  self::$yy_shift_ofst    For each state, the offset into self::$yy_action for
**                          shifting terminals.
**  self::$yy_reduce_ofst   For each state, the offset into self::$yy_action for
**                          shifting non-terminals after a reduce.
**  self::$yy_default       Default action for each state.
*/
    const YY_SZ_ACTTAB = 79;
static public $yy_action = array(
 /*     0 */    45,    7,    2,   27,   50,   48,   47,   44,   33,   46,
 /*    10 */    43,   39,   40,    8,   25,   18,   19,   13,   17,    9,
 /*    20 */    10,   45,   88,    6,   49,   50,   48,   47,   44,   33,
 /*    30 */    46,   14,    8,    1,   18,   19,   13,   17,    9,   10,
 /*    40 */    41,   16,   22,    8,   12,   21,   23,   51,    4,   28,
 /*    50 */    20,   26,   32,   19,    3,   29,    9,   10,   11,   22,
 /*    60 */    36,   15,   21,   23,   42,    5,   31,   20,   38,   37,
 /*    70 */    34,   35,   24,   19,   30,   74,    9,   10,   11,
    );
    static public $yy_lookahead = array(
 /*     0 */    24,    3,   26,   27,   28,   29,   30,   31,   32,   33,
 /*    10 */     4,   19,   20,    7,    2,    9,   10,   11,   12,   13,
 /*    20 */    14,   24,   17,   18,   27,   28,   29,   30,   31,   32,
 /*    30 */    33,    2,    7,    3,    9,   10,   11,   12,   13,   14,
 /*    40 */     4,    5,   28,    7,    8,   31,   32,    4,   34,   35,
 /*    50 */    36,    2,    2,   10,    3,    2,   13,   14,   15,   28,
 /*    60 */     6,    1,   31,   32,    6,   21,   35,   36,   22,   23,
 /*    70 */    24,   25,    2,   10,    2,   37,   13,   14,   15,
);
    const YY_SHIFT_USE_DFLT = -3;
    const YY_SHIFT_MAX = 19;
    static public $yy_shift_ofst = array(
 /*     0 */    -3,   25,    6,   63,   43,   36,   60,   -3,   58,   72,
 /*    10 */    70,   53,   30,   12,   -2,   29,   54,   49,   51,   50,
);
    const YY_REDUCE_USE_DFLT = -25;
    const YY_REDUCE_MAX = 7;
    static public $yy_reduce_ofst = array(
 /*     0 */     5,  -24,   -3,   14,   31,   46,   -8,   44,
);
    static public $yyExpectedTokens = array(
        /* 0 */ array(),
        /* 1 */ array(7, 9, 10, 11, 12, 13, 14, ),
        /* 2 */ array(4, 7, 9, 10, 11, 12, 13, 14, ),
        /* 3 */ array(10, 13, 14, 15, ),
        /* 4 */ array(4, 10, 13, 14, 15, ),
        /* 5 */ array(4, 5, 7, 8, ),
        /* 6 */ array(1, ),
        /* 7 */ array(),
        /* 8 */ array(6, ),
        /* 9 */ array(2, ),
        /* 10 */ array(2, ),
        /* 11 */ array(2, ),
        /* 12 */ array(3, ),
        /* 13 */ array(2, ),
        /* 14 */ array(3, ),
        /* 15 */ array(2, ),
        /* 16 */ array(6, ),
        /* 17 */ array(2, ),
        /* 18 */ array(3, ),
        /* 19 */ array(2, ),
        /* 20 */ array(),
        /* 21 */ array(),
        /* 22 */ array(),
        /* 23 */ array(),
        /* 24 */ array(),
        /* 25 */ array(),
        /* 26 */ array(),
        /* 27 */ array(),
        /* 28 */ array(),
        /* 29 */ array(),
        /* 30 */ array(),
        /* 31 */ array(),
        /* 32 */ array(),
        /* 33 */ array(),
        /* 34 */ array(),
        /* 35 */ array(),
        /* 36 */ array(),
        /* 37 */ array(),
        /* 38 */ array(),
        /* 39 */ array(),
        /* 40 */ array(),
        /* 41 */ array(),
        /* 42 */ array(),
        /* 43 */ array(),
        /* 44 */ array(),
        /* 45 */ array(),
        /* 46 */ array(),
        /* 47 */ array(),
        /* 48 */ array(),
        /* 49 */ array(),
        /* 50 */ array(),
        /* 51 */ array(),
);
    static public $yy_default = array(
 /*     0 */    54,   87,   87,   87,   87,   87,   52,   58,   87,   87,
 /*    10 */    87,   87,   87,   87,   87,   87,   87,   87,   87,   87,
 /*    20 */    80,   78,   77,   79,   85,   82,   83,   66,   76,   86,
 /*    30 */    84,   75,   81,   71,   60,   61,   62,   59,   57,   53,
 /*    40 */    55,   56,   63,   64,   70,   72,   73,   69,   68,   65,
 /*    50 */    67,   74,
);
/* The next thing included is series of defines which control
** various aspects of the generated parser.
**    self::YYNOCODE      is a number which corresponds
**                        to no legal terminal or nonterminal number.  This
**                        number is used to fill in empty slots of the hash 
**                        table.
**    self::YYFALLBACK    If defined, this indicates that one or more tokens
**                        have fall-back values which should be used if the
**                        original value of the token will not parse.
**    self::YYSTACKDEPTH  is the maximum depth of the parser's stack.
**    self::YYNSTATE      the combined number of states.
**    self::YYNRULE       the number of rules in the grammar
**    self::YYERRORSYMBOL is the code number of the error symbol.  If not
**                        defined, then do no error processing.
*/
    const YYNOCODE = 38;
    const YYSTACKDEPTH = 100;
    const YYNSTATE = 52;
    const YYNRULE = 35;
    const YYERRORSYMBOL = 16;
    const YYERRSYMDT = 'yy0';
    const YYFALLBACK = 0;
    /** The next table maps tokens into fallback tokens.  If a construct
     * like the following:
     * 
     *      %fallback ID X Y Z.
     *
     * appears in the grammer, then ID becomes a fallback token for X, Y,
     * and Z.  Whenever one of the tokens X, Y, or Z is input to the parser
     * but it does not parse, the type of the token is changed to ID and
     * the parse is retried before an error is thrown.
     */
    static public $yyFallback = array(
    );
    /**
     * Turn parser tracing on by giving a stream to which to write the trace
     * and a prompt to preface each trace message.  Tracing is turned off
     * by making either argument NULL 
     *
     * Inputs:
     * 
     * - A stream resource to which trace output should be written.
     *   If NULL, then tracing is turned off.
     * - A prefix string written at the beginning of every
     *   line of trace output.  If NULL, then tracing is
     *   turned off.
     *
     * Outputs:
     * 
     * - None.
     * @param resource
     * @param string
     */
    static function Trace($TraceFILE, $zTracePrompt)
    {
        if (!$TraceFILE) {
            $zTracePrompt = 0;
        } elseif (!$zTracePrompt) {
            $TraceFILE = 0;
        }
        self::$yyTraceFILE = $TraceFILE;
        self::$yyTracePrompt = $zTracePrompt;
    }

    /**
     * Output debug information to output (php://output stream)
     */
    static function PrintTrace()
    {
        self::$yyTraceFILE = fopen('php://output', 'w');
        self::$yyTracePrompt = '';
    }

    /**
     * @var resource|0
     */
    static public $yyTraceFILE;
    /**
     * String to prepend to debug output
     * @var string|0
     */
    static public $yyTracePrompt;
    /**
     * @var int
     */
    public $yyidx;                    /* Index of top element in stack */
    /**
     * @var int
     */
    public $yyerrcnt;                 /* Shifts left before out of the error */
    /**
     * @var array
     */
    public $yystack = array();  /* The parser's stack */

    /**
     * For tracing shifts, the names of all terminals and nonterminals
     * are required.  The following table supplies these names
     * @var array
     */
    static public $yyTokenName = array( 
  '$',             'METHOD',        'ID',            'LCURLY',      
  'RCURLY',        'QUERY',         'STRING',        'ORDER_BY',    
  'ASSOCIATION',   'THROUGH',       'TABLE',         'ASSOCIATION_TYPE',
  'PROPERTY',      'COLUMN',        'REFERENCED_COLUMN',  'INVERSE_COLUMN',
  'error',         'start',         'topStatementList',  'topStatement',
  'method',        'methodStatementList',  'methodStatement',  'query',       
  'orderBy',       'association',   'associationStatementList',  'associationStatement',
  'table',         'associationType',  'property',      'column',      
  'referencedColumn',  'through',       'throughStatementList',  'throughStatement',
  'inverseColumn',
    );

    /**
     * For tracing reduce actions, the names of all rules are required.
     * @var array
     */
    static public $yyRuleName = array(
 /*   0 */ "start ::= topStatementList",
 /*   1 */ "topStatementList ::= topStatementList topStatement",
 /*   2 */ "topStatementList ::=",
 /*   3 */ "topStatement ::= method",
 /*   4 */ "method ::= METHOD ID LCURLY methodStatementList RCURLY",
 /*   5 */ "methodStatementList ::= methodStatementList methodStatement",
 /*   6 */ "methodStatementList ::=",
 /*   7 */ "methodStatement ::= query",
 /*   8 */ "methodStatement ::= orderBy",
 /*   9 */ "methodStatement ::= association",
 /*  10 */ "query ::= QUERY STRING",
 /*  11 */ "orderBy ::= ORDER_BY STRING",
 /*  12 */ "association ::= ASSOCIATION LCURLY associationStatementList RCURLY",
 /*  13 */ "associationStatementList ::= associationStatementList associationStatement",
 /*  14 */ "associationStatementList ::= associationStatement",
 /*  15 */ "associationStatement ::= table",
 /*  16 */ "associationStatement ::= associationType",
 /*  17 */ "associationStatement ::= property",
 /*  18 */ "associationStatement ::= column",
 /*  19 */ "associationStatement ::= referencedColumn",
 /*  20 */ "associationStatement ::= orderBy",
 /*  21 */ "associationStatement ::= through",
 /*  22 */ "through ::= THROUGH LCURLY throughStatementList RCURLY",
 /*  23 */ "throughStatementList ::= throughStatementList throughStatement",
 /*  24 */ "throughStatementList ::= throughStatement",
 /*  25 */ "throughStatement ::= table",
 /*  26 */ "throughStatement ::= column",
 /*  27 */ "throughStatement ::= referencedColumn",
 /*  28 */ "throughStatement ::= inverseColumn",
 /*  29 */ "table ::= TABLE ID",
 /*  30 */ "associationType ::= ASSOCIATION_TYPE ID",
 /*  31 */ "property ::= PROPERTY ID",
 /*  32 */ "column ::= COLUMN ID",
 /*  33 */ "referencedColumn ::= REFERENCED_COLUMN ID",
 /*  34 */ "inverseColumn ::= INVERSE_COLUMN ID",
    );

    /**
     * This function returns the symbolic name associated with a token
     * value.
     * @param int
     * @return string
     */
    function tokenName($tokenType)
    {
        if ($tokenType === 0) {
            return 'End of Input';
        }
        if ($tokenType > 0 && $tokenType < count(self::$yyTokenName)) {
            return self::$yyTokenName[$tokenType];
        } else {
            return "Unknown";
        }
    }

    /**
     * The following function deletes the value associated with a
     * symbol.  The symbol can be either a terminal or nonterminal.
     * @param int the symbol code
     * @param mixed the symbol's value
     */
    static function yy_destructor($yymajor, $yypminor)
    {
        switch ($yymajor) {
        /* Here is inserted the actions which take place when a
        ** terminal or non-terminal is destroyed.  This can happen
        ** when the symbol is popped from the stack during a
        ** reduce or during error processing or when a parser is 
        ** being destroyed before it is finished parsing.
        **
        ** Note: during a reduce, the only symbols destroyed are those
        ** which appear on the RHS of the rule, but which are not used
        ** inside the C code.
        */
            default:  break;   /* If no destructor action specified: do nothing */
        }
    }

    /**
     * Pop the parser's stack once.
     *
     * If there is a destructor routine associated with the token which
     * is popped from the stack, then call it.
     *
     * Return the major token number for the symbol popped.
     * @param MapperParseryyParser
     * @return int
     */
    function yy_pop_parser_stack()
    {
        if (!count($this->yystack)) {
            return;
        }
        $yytos = array_pop($this->yystack);
        if (self::$yyTraceFILE && $this->yyidx >= 0) {
            fwrite(self::$yyTraceFILE,
                self::$yyTracePrompt . 'Popping ' . self::$yyTokenName[$yytos->major] .
                    "\n");
        }
        $yymajor = $yytos->major;
        self::yy_destructor($yymajor, $yytos->minor);
        $this->yyidx--;
        return $yymajor;
    }

    /**
     * Deallocate and destroy a parser.  Destructors are all called for
     * all stack elements before shutting the parser down.
     */
    function __destruct()
    {
        while ($this->yyidx >= 0) {
            $this->yy_pop_parser_stack();
        }
        if (is_resource(self::$yyTraceFILE)) {
            fclose(self::$yyTraceFILE);
        }
    }

    /**
     * Based on the current state and parser stack, get a list of all
     * possible lookahead tokens
     * @param int
     * @return array
     */
    function yy_get_expected_tokens($token)
    {
        $state = $this->yystack[$this->yyidx]->stateno;
        $expected = self::$yyExpectedTokens[$state];
        if (in_array($token, self::$yyExpectedTokens[$state], true)) {
            return $expected;
        }
        $stack = $this->yystack;
        $yyidx = $this->yyidx;
        do {
            $yyact = $this->yy_find_shift_action($token);
            if ($yyact >= self::YYNSTATE && $yyact < self::YYNSTATE + self::YYNRULE) {
                // reduce action
                $done = 0;
                do {
                    if ($done++ == 100) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // too much recursion prevents proper detection
                        // so give up
                        return array_unique($expected);
                    }
                    $yyruleno = $yyact - self::YYNSTATE;
                    $this->yyidx -= self::$yyRuleInfo[$yyruleno]['rhs'];
                    $nextstate = $this->yy_find_reduce_action(
                        $this->yystack[$this->yyidx]->stateno,
                        self::$yyRuleInfo[$yyruleno]['lhs']);
                    if (isset(self::$yyExpectedTokens[$nextstate])) {
                        $expected += self::$yyExpectedTokens[$nextstate];
                            if (in_array($token,
                                  self::$yyExpectedTokens[$nextstate], true)) {
                            $this->yyidx = $yyidx;
                            $this->yystack = $stack;
                            return array_unique($expected);
                        }
                    }
                    if ($nextstate < self::YYNSTATE) {
                        // we need to shift a non-terminal
                        $this->yyidx++;
                        $x = new MapperParseryyStackEntry;
                        $x->stateno = $nextstate;
                        $x->major = self::$yyRuleInfo[$yyruleno]['lhs'];
                        $this->yystack[$this->yyidx] = $x;
                        continue 2;
                    } elseif ($nextstate == self::YYNSTATE + self::YYNRULE + 1) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // the last token was just ignored, we can't accept
                        // by ignoring input, this is in essence ignoring a
                        // syntax error!
                        return array_unique($expected);
                    } elseif ($nextstate === self::YY_NO_ACTION) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // input accepted, but not shifted (I guess)
                        return $expected;
                    } else {
                        $yyact = $nextstate;
                    }
                } while (true);
            }
            break;
        } while (true);
        return array_unique($expected);
    }

    /**
     * Based on the parser state and current parser stack, determine whether
     * the lookahead token is possible.
     * 
     * The parser will convert the token value to an error token if not.  This
     * catches some unusual edge cases where the parser would fail.
     * @param int
     * @return bool
     */
    function yy_is_expected_token($token)
    {
        if ($token === 0) {
            return true; // 0 is not part of this
        }
        $state = $this->yystack[$this->yyidx]->stateno;
        if (in_array($token, self::$yyExpectedTokens[$state], true)) {
            return true;
        }
        $stack = $this->yystack;
        $yyidx = $this->yyidx;
        do {
            $yyact = $this->yy_find_shift_action($token);
            if ($yyact >= self::YYNSTATE && $yyact < self::YYNSTATE + self::YYNRULE) {
                // reduce action
                $done = 0;
                do {
                    if ($done++ == 100) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // too much recursion prevents proper detection
                        // so give up
                        return true;
                    }
                    $yyruleno = $yyact - self::YYNSTATE;
                    $this->yyidx -= self::$yyRuleInfo[$yyruleno]['rhs'];
                    $nextstate = $this->yy_find_reduce_action(
                        $this->yystack[$this->yyidx]->stateno,
                        self::$yyRuleInfo[$yyruleno]['lhs']);
                    if (isset(self::$yyExpectedTokens[$nextstate]) &&
                          in_array($token, self::$yyExpectedTokens[$nextstate], true)) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        return true;
                    }
                    if ($nextstate < self::YYNSTATE) {
                        // we need to shift a non-terminal
                        $this->yyidx++;
                        $x = new MapperParseryyStackEntry;
                        $x->stateno = $nextstate;
                        $x->major = self::$yyRuleInfo[$yyruleno]['lhs'];
                        $this->yystack[$this->yyidx] = $x;
                        continue 2;
                    } elseif ($nextstate == self::YYNSTATE + self::YYNRULE + 1) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        if (!$token) {
                            // end of input: this is valid
                            return true;
                        }
                        // the last token was just ignored, we can't accept
                        // by ignoring input, this is in essence ignoring a
                        // syntax error!
                        return false;
                    } elseif ($nextstate === self::YY_NO_ACTION) {
                        $this->yyidx = $yyidx;
                        $this->yystack = $stack;
                        // input accepted, but not shifted (I guess)
                        return true;
                    } else {
                        $yyact = $nextstate;
                    }
                } while (true);
            }
            break;
        } while (true);
        $this->yyidx = $yyidx;
        $this->yystack = $stack;
        return true;
    }

    /**
     * Find the appropriate action for a parser given the terminal
     * look-ahead token iLookAhead.
     *
     * If the look-ahead token is YYNOCODE, then check to see if the action is
     * independent of the look-ahead.  If it is, return the action, otherwise
     * return YY_NO_ACTION.
     * @param int The look-ahead token
     */
    function yy_find_shift_action($iLookAhead)
    {
        $stateno = $this->yystack[$this->yyidx]->stateno;
     
        /* if ($this->yyidx < 0) return self::YY_NO_ACTION;  */
        if (!isset(self::$yy_shift_ofst[$stateno])) {
            // no shift actions
            return self::$yy_default[$stateno];
        }
        $i = self::$yy_shift_ofst[$stateno];
        if ($i === self::YY_SHIFT_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead == self::YYNOCODE) {
            return self::YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= self::YY_SZ_ACTTAB ||
              self::$yy_lookahead[$i] != $iLookAhead) {
            if (count(self::$yyFallback) && $iLookAhead < count(self::$yyFallback)
                   && ($iFallback = self::$yyFallback[$iLookAhead]) != 0) {
                if (self::$yyTraceFILE) {
                    fwrite(self::$yyTraceFILE, self::$yyTracePrompt . "FALLBACK " .
                        self::$yyTokenName[$iLookAhead] . " => " .
                        self::$yyTokenName[$iFallback] . "\n");
                }
                return $this->yy_find_shift_action($iFallback);
            }
            return self::$yy_default[$stateno];
        } else {
            return self::$yy_action[$i];
        }
    }

    /**
     * Find the appropriate action for a parser given the non-terminal
     * look-ahead token $iLookAhead.
     *
     * If the look-ahead token is self::YYNOCODE, then check to see if the action is
     * independent of the look-ahead.  If it is, return the action, otherwise
     * return self::YY_NO_ACTION.
     * @param int Current state number
     * @param int The look-ahead token
     */
    function yy_find_reduce_action($stateno, $iLookAhead)
    {
        /* $stateno = $this->yystack[$this->yyidx]->stateno; */

        if (!isset(self::$yy_reduce_ofst[$stateno])) {
            return self::$yy_default[$stateno];
        }
        $i = self::$yy_reduce_ofst[$stateno];
        if ($i == self::YY_REDUCE_USE_DFLT) {
            return self::$yy_default[$stateno];
        }
        if ($iLookAhead == self::YYNOCODE) {
            return self::YY_NO_ACTION;
        }
        $i += $iLookAhead;
        if ($i < 0 || $i >= self::YY_SZ_ACTTAB ||
              self::$yy_lookahead[$i] != $iLookAhead) {
            return self::$yy_default[$stateno];
        } else {
            return self::$yy_action[$i];
        }
    }

    /**
     * Perform a shift action.
     * @param int The new state to shift in
     * @param int The major token to shift in
     * @param mixed the minor token to shift in
     */
    function yy_shift($yyNewState, $yyMajor, $yypMinor)
    {
        $this->yyidx++;
        if ($this->yyidx >= self::YYSTACKDEPTH) {
            $this->yyidx--;
            if (self::$yyTraceFILE) {
                fprintf(self::$yyTraceFILE, "%sStack Overflow!\n", self::$yyTracePrompt);
            }
            while ($this->yyidx >= 0) {
                $this->yy_pop_parser_stack();
            }
            /* Here code is inserted which will execute if the parser
            ** stack ever overflows */
            return;
        }
        $yytos = new MapperParseryyStackEntry;
        $yytos->stateno = $yyNewState;
        $yytos->major = $yyMajor;
        $yytos->minor = $yypMinor;
        array_push($this->yystack, $yytos);
        if (self::$yyTraceFILE && $this->yyidx > 0) {
            fprintf(self::$yyTraceFILE, "%sShift %d\n", self::$yyTracePrompt,
                $yyNewState);
            fprintf(self::$yyTraceFILE, "%sStack:", self::$yyTracePrompt);
            for($i = 1; $i <= $this->yyidx; $i++) {
                fprintf(self::$yyTraceFILE, " %s",
                    self::$yyTokenName[$this->yystack[$i]->major]);
            }
            fwrite(self::$yyTraceFILE,"\n");
        }
    }

    /**
     * The following table contains information about every rule that
     * is used during the reduce.
     *
     * <pre>
     * array(
     *  array(
     *   int $lhs;         Symbol on the left-hand side of the rule
     *   int $nrhs;     Number of right-hand side symbols in the rule
     *  ),...
     * );
     * </pre>
     */
    static public $yyRuleInfo = array(
  array( 'lhs' => 17, 'rhs' => 1 ),
  array( 'lhs' => 18, 'rhs' => 2 ),
  array( 'lhs' => 18, 'rhs' => 0 ),
  array( 'lhs' => 19, 'rhs' => 1 ),
  array( 'lhs' => 20, 'rhs' => 5 ),
  array( 'lhs' => 21, 'rhs' => 2 ),
  array( 'lhs' => 21, 'rhs' => 0 ),
  array( 'lhs' => 22, 'rhs' => 1 ),
  array( 'lhs' => 22, 'rhs' => 1 ),
  array( 'lhs' => 22, 'rhs' => 1 ),
  array( 'lhs' => 23, 'rhs' => 2 ),
  array( 'lhs' => 24, 'rhs' => 2 ),
  array( 'lhs' => 25, 'rhs' => 4 ),
  array( 'lhs' => 26, 'rhs' => 2 ),
  array( 'lhs' => 26, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 27, 'rhs' => 1 ),
  array( 'lhs' => 33, 'rhs' => 4 ),
  array( 'lhs' => 34, 'rhs' => 2 ),
  array( 'lhs' => 34, 'rhs' => 1 ),
  array( 'lhs' => 35, 'rhs' => 1 ),
  array( 'lhs' => 35, 'rhs' => 1 ),
  array( 'lhs' => 35, 'rhs' => 1 ),
  array( 'lhs' => 35, 'rhs' => 1 ),
  array( 'lhs' => 28, 'rhs' => 2 ),
  array( 'lhs' => 29, 'rhs' => 2 ),
  array( 'lhs' => 30, 'rhs' => 2 ),
  array( 'lhs' => 31, 'rhs' => 2 ),
  array( 'lhs' => 32, 'rhs' => 2 ),
  array( 'lhs' => 36, 'rhs' => 2 ),
    );

    /**
     * The following table contains a mapping of reduce action to method name
     * that handles the reduction.
     * 
     * If a rule is not set, it has no handler.
     */
    static public $yyReduceMap = array(
        4 => 4,
        5 => 5,
        7 => 7,
        8 => 8,
        20 => 8,
        9 => 9,
        10 => 10,
        11 => 10,
        29 => 10,
        30 => 10,
        31 => 10,
        32 => 10,
        33 => 10,
        34 => 10,
        12 => 12,
        13 => 13,
        23 => 13,
        14 => 14,
        24 => 14,
        15 => 15,
        25 => 15,
        16 => 16,
        17 => 17,
        18 => 18,
        26 => 18,
        19 => 19,
        27 => 19,
        21 => 21,
        22 => 22,
        28 => 28,
    );
    /* Beginning here are the reduction cases.  A typical example
    ** follows:
    **  #line <lineno> <grammarfile>
    **   function yy_r0($yymsp){ ... }           // User supplied code
    **  #line <lineno> <thisfile>
    */
#line 93 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r4(){
        $this->_ast->addMethod($this->yystack[$this->yyidx + -3]->minor, @$this->yystack[$this->yyidx + -1]->minor['query'], @$this->yystack[$this->yyidx + -1]->minor['orderBy'], @$this->yystack[$this->yyidx + -1]->minor['associations']);
    }
#line 951 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 97 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r5(){
        if (!is_array($this->yystack[$this->yyidx + -1]->minor)) {
            $this->yystack[$this->yyidx + -1]->minor = array();
        }
        $this->_retvalue = $this->yystack[$this->yyidx + -1]->minor;
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            if ($key == 'association') {
                $this->_retvalue['associations'][] = $this->yystack[$this->yyidx + 0]->minor[$key];
                continue;
            }
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 966 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 112 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r7(){ $this->_retvalue['query'] = trim($this->yystack[$this->yyidx + 0]->minor, '"');     }
#line 969 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 113 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r8(){ $this->_retvalue['orderBy'] = trim($this->yystack[$this->yyidx + 0]->minor, '"');     }
#line 972 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 114 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r9(){ $this->_retvalue['association'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 975 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 116 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r10(){ $this->_retvalue = $this->yystack[$this->yyidx + 0]->minor;     }
#line 978 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 120 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r12(){
        $requiredKeys = array('table', 'type', 'property');
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $this->yystack[$this->yyidx + -1]->minor)) {
                throw new Exception("The [ $requiredKey ] statement was not found in the 'association' statement on line {$this->_mapperLexer->line}. An 'association' statement must contain the 'table', 'type', and 'property' statements.");
            }
        }
        $association = $this->_ast->createElement('association');
        foreach (array_keys((array)$this->yystack[$this->yyidx + -1]->minor) as $key) {
            if ($key == 'through') {
                $association->appendChild($this->yystack[$this->yyidx + -1]->minor[$key]);
                continue;
            }
            $association->setAttribute($key, $this->yystack[$this->yyidx + -1]->minor[$key]);
        }
        $this->_retvalue = $association;
    }
#line 997 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 138 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r13(){
        if (!is_array($this->yystack[$this->yyidx + -1]->minor)) {
            $this->yystack[$this->yyidx + -1]->minor = array();
        }
        $this->_retvalue = $this->yystack[$this->yyidx + -1]->minor;
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 1008 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 147 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r14(){
        foreach (array_keys($this->yystack[$this->yyidx + 0]->minor) as $key) {
            $this->_retvalue[$key] = $this->yystack[$this->yyidx + 0]->minor[$key];
        }
    }
#line 1015 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 153 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r15(){ $this->_retvalue['table'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1018 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 154 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r16(){ $this->_retvalue['type'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1021 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 155 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r17(){ $this->_retvalue['property'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1024 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 156 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r18(){ $this->_retvalue['column'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1027 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 157 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r19(){ $this->_retvalue['referencedColumn'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1030 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 159 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r21(){ $this->_retvalue['through'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1033 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 161 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r22(){
        if (!array_key_exists('table', $this->yystack[$this->yyidx + -1]->minor)) {
            throw new Exception("The [ table ] statement was not found in the 'through' statement on line {$this->_mapperLexer->line}. An 'association' statement must contain the 'table' statement.");
        }

        $through = $this->_ast->createElement('through');
        foreach (array_keys($this->yystack[$this->yyidx + -1]->minor) as $key) {
            $through->setAttribute($key, $this->yystack[$this->yyidx + -1]->minor[$key]);
        }
        $this->_retvalue = $through;
    }
#line 1046 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
#line 191 "src/Piece/ORM/Mapper/Parser/MapperParser.y"
    function yy_r28(){ $this->_retvalue['inverseColumn'] = $this->yystack[$this->yyidx + 0]->minor;     }
#line 1049 "src/Piece/ORM/Mapper/Parser/MapperParser.php"

    /**
     * placeholder for the left hand side in a reduce operation.
     * 
     * For a parser with a rule like this:
     * <pre>
     * rule(A) ::= B. { A = 1; }
     * </pre>
     * 
     * The parser will translate to something like:
     * 
     * <code>
     * function yy_r0(){$this->_retvalue = 1;}
     * </code>
     */
    private $_retvalue;

    /**
     * Perform a reduce action and the shift that must immediately
     * follow the reduce.
     * 
     * For a rule such as:
     * 
     * <pre>
     * A ::= B blah C. { dosomething(); }
     * </pre>
     * 
     * This function will first call the action, if any, ("dosomething();" in our
     * example), and then it will pop three states from the stack,
     * one for each entry on the right-hand side of the expression
     * (B, blah, and C in our example rule), and then push the result of the action
     * back on to the stack with the resulting state reduced to (as described in the .out
     * file)
     * @param int Number of the rule by which to reduce
     */
    function yy_reduce($yyruleno)
    {
        //int $yygoto;                     /* The next state */
        //int $yyact;                      /* The next action */
        //mixed $yygotominor;        /* The LHS of the rule reduced */
        //MapperParseryyStackEntry $yymsp;            /* The top of the parser's stack */
        //int $yysize;                     /* Amount to pop the stack */
        $yymsp = $this->yystack[$this->yyidx];
        if (self::$yyTraceFILE && $yyruleno >= 0 
              && $yyruleno < count(self::$yyRuleName)) {
            fprintf(self::$yyTraceFILE, "%sReduce (%d) [%s].\n",
                self::$yyTracePrompt, $yyruleno,
                self::$yyRuleName[$yyruleno]);
        }

        $this->_retvalue = $yy_lefthand_side = null;
        if (array_key_exists($yyruleno, self::$yyReduceMap)) {
            // call the action
            $this->_retvalue = null;
            $this->{'yy_r' . self::$yyReduceMap[$yyruleno]}();
            $yy_lefthand_side = $this->_retvalue;
        }
        $yygoto = self::$yyRuleInfo[$yyruleno]['lhs'];
        $yysize = self::$yyRuleInfo[$yyruleno]['rhs'];
        $this->yyidx -= $yysize;
        for($i = $yysize; $i; $i--) {
            // pop all of the right-hand side parameters
            array_pop($this->yystack);
        }
        $yyact = $this->yy_find_reduce_action($this->yystack[$this->yyidx]->stateno, $yygoto);
        if ($yyact < self::YYNSTATE) {
            /* If we are not debugging and the reduce action popped at least
            ** one element off the stack, then we can push the new element back
            ** onto the stack here, and skip the stack overflow test in yy_shift().
            ** That gives a significant speed improvement. */
            if (!self::$yyTraceFILE && $yysize) {
                $this->yyidx++;
                $x = new MapperParseryyStackEntry;
                $x->stateno = $yyact;
                $x->major = $yygoto;
                $x->minor = $yy_lefthand_side;
                $this->yystack[$this->yyidx] = $x;
            } else {
                $this->yy_shift($yyact, $yygoto, $yy_lefthand_side);
            }
        } elseif ($yyact == self::YYNSTATE + self::YYNRULE + 1) {
            $this->yy_accept();
        }
    }

    /**
     * The following code executes when the parse fails
     * 
     * Code from %parse_fail is inserted here
     */
    function yy_parse_failed()
    {
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sFail!\n", self::$yyTracePrompt);
        }
        while ($this->yyidx >= 0) {
            $this->yy_pop_parser_stack();
        }
        /* Here code is inserted which will be executed whenever the
        ** parser fails */
    }

    /**
     * The following code executes when a syntax error first occurs.
     * 
     * %syntax_error code is inserted here
     * @param int The major type of the error token
     * @param mixed The minor type of the error token
     */
    function yy_syntax_error($yymajor, $TOKEN)
    {
#line 57 "src/Piece/ORM/Mapper/Parser/MapperParser.y"

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
#line 1179 "src/Piece/ORM/Mapper/Parser/MapperParser.php"
    }

    /**
     * The following is executed when the parser accepts
     * 
     * %parse_accept code is inserted here
     */
    function yy_accept()
    {
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sAccept!\n", self::$yyTracePrompt);
        }
        while ($this->yyidx >= 0) {
            $stack = $this->yy_pop_parser_stack();
        }
        /* Here code is inserted which will be executed whenever the
        ** parser accepts */
    }

    /**
     * The main parser program.
     * 
     * The first argument is the major token number.  The second is
     * the token value string as scanned from the input.
     *
     * @param int the token number
     * @param mixed the token value
     * @param mixed any extra arguments that should be passed to handlers
     */
    function doParse($yymajor, $yytokenvalue)
    {
//        $yyact;            /* The parser action. */
//        $yyendofinput;     /* True if we are at the end of input */
        $yyerrorhit = 0;   /* True if yymajor has invoked an error */
        
        /* (re)initialize the parser, if necessary */
        if ($this->yyidx === null || $this->yyidx < 0) {
            /* if ($yymajor == 0) return; // not sure why this was here... */
            $this->yyidx = 0;
            $this->yyerrcnt = -1;
            $x = new MapperParseryyStackEntry;
            $x->stateno = 0;
            $x->major = 0;
            $this->yystack = array();
            array_push($this->yystack, $x);
        }
        $yyendofinput = ($yymajor==0);
        
        if (self::$yyTraceFILE) {
            fprintf(self::$yyTraceFILE, "%sInput %s\n",
                self::$yyTracePrompt, self::$yyTokenName[$yymajor]);
        }
        
        do {
            $yyact = $this->yy_find_shift_action($yymajor);
            if ($yymajor < self::YYERRORSYMBOL &&
                  !$this->yy_is_expected_token($yymajor)) {
                // force a syntax error
                $yyact = self::YY_ERROR_ACTION;
            }
            if ($yyact < self::YYNSTATE) {
                $this->yy_shift($yyact, $yymajor, $yytokenvalue);
                $this->yyerrcnt--;
                if ($yyendofinput && $this->yyidx >= 0) {
                    $yymajor = 0;
                } else {
                    $yymajor = self::YYNOCODE;
                }
            } elseif ($yyact < self::YYNSTATE + self::YYNRULE) {
                $this->yy_reduce($yyact - self::YYNSTATE);
            } elseif ($yyact == self::YY_ERROR_ACTION) {
                if (self::$yyTraceFILE) {
                    fprintf(self::$yyTraceFILE, "%sSyntax Error!\n",
                        self::$yyTracePrompt);
                }
                if (self::YYERRORSYMBOL) {
                    /* A syntax error has occurred.
                    ** The response to an error depends upon whether or not the
                    ** grammar defines an error token "ERROR".  
                    **
                    ** This is what we do if the grammar does define ERROR:
                    **
                    **  * Call the %syntax_error function.
                    **
                    **  * Begin popping the stack until we enter a state where
                    **    it is legal to shift the error symbol, then shift
                    **    the error symbol.
                    **
                    **  * Set the error count to three.
                    **
                    **  * Begin accepting and shifting new tokens.  No new error
                    **    processing will occur until three tokens have been
                    **    shifted successfully.
                    **
                    */
                    if ($this->yyerrcnt < 0) {
                        $this->yy_syntax_error($yymajor, $yytokenvalue);
                    }
                    $yymx = $this->yystack[$this->yyidx]->major;
                    if ($yymx == self::YYERRORSYMBOL || $yyerrorhit ){
                        if (self::$yyTraceFILE) {
                            fprintf(self::$yyTraceFILE, "%sDiscard input token %s\n",
                                self::$yyTracePrompt, self::$yyTokenName[$yymajor]);
                        }
                        $this->yy_destructor($yymajor, $yytokenvalue);
                        $yymajor = self::YYNOCODE;
                    } else {
                        while ($this->yyidx >= 0 &&
                                 $yymx != self::YYERRORSYMBOL &&
        ($yyact = $this->yy_find_shift_action(self::YYERRORSYMBOL)) >= self::YYNSTATE
                              ){
                            $this->yy_pop_parser_stack();
                        }
                        if ($this->yyidx < 0 || $yymajor==0) {
                            $this->yy_destructor($yymajor, $yytokenvalue);
                            $this->yy_parse_failed();
                            $yymajor = self::YYNOCODE;
                        } elseif ($yymx != self::YYERRORSYMBOL) {
                            $u2 = 0;
                            $this->yy_shift($yyact, self::YYERRORSYMBOL, $u2);
                        }
                    }
                    $this->yyerrcnt = 3;
                    $yyerrorhit = 1;
                } else {
                    /* YYERRORSYMBOL is not defined */
                    /* This is what we do if the grammar does not define ERROR:
                    **
                    **  * Report an error message, and throw away the input token.
                    **
                    **  * If the input token is $, then fail the parse.
                    **
                    ** As before, subsequent error messages are suppressed until
                    ** three input tokens have been successfully shifted.
                    */
                    if ($this->yyerrcnt <= 0) {
                        $this->yy_syntax_error($yymajor, $yytokenvalue);
                    }
                    $this->yyerrcnt = 3;
                    $this->yy_destructor($yymajor, $yytokenvalue);
                    if ($yyendofinput) {
                        $this->yy_parse_failed();
                    }
                    $yymajor = self::YYNOCODE;
                }
            } else {
                $this->yy_accept();
                $yymajor = self::YYNOCODE;
            }            
        } while ($yymajor != self::YYNOCODE && $this->yyidx >= 0);
    }
}