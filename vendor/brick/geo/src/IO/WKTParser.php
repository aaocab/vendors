<?php

declare(strict_types=1);

namespace Brick\Geo\IO;

use Brick\Geo\Exception\GeometryIOException;

/**
 * Well-Known Text parser.
 */
class WKTParser
{
    const T_WORD   = 1;
    const T_NUMBER = 2;

    const REGEX_WORD   = '([a-z]+)';
    const REGEX_NUMBER = '(\-?[0-9]+(?:\.[0-9]+)?(?:e[\+\-]?[0-9]+)?)';

    /**
     * An array of all tokens.
     *
     * @var array
     */
    protected $tokens = [];

    /**
     * Class constructor.
     *
     * @param string $wkt
     */
    public function __construct(string $wkt)
    {
        $this->scan($wkt);
    }

    /**
     * @return array
     */
    protected function getRegex() : array
    {
        return [
            self::T_WORD   => self::REGEX_WORD,
            self::T_NUMBER => self::REGEX_NUMBER,
        ];
    }

    /**
     * @param string $wkt
     *
     * @return void
     */
    private function scan(string $wkt) : void
    {
        $regex = $this->getRegex();
        $regex[] = '\s+';
        $regex[] = '(.+?)';

        $regex = '/' . implode('|', $regex) . '/i';

        preg_match_all($regex, $wkt, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            foreach ($match as $key => $value) {
                if ($key === 0) {
                    continue;
                }

                if ($value !== '') {
                    $this->tokens[$index] = [$key, $value];
                }
            }
        }

        ksort($this->tokens);
        reset($this->tokens);
    }

    /**
     * @return array|null The next token, or null if there are no more tokens.
     */
    private function nextToken() : ?array
    {
        $token = current($this->tokens);

        if ($token === false) {
            return null;
        } else {
            next($this->tokens);
        }

        return $token;
    }

    /**
     * @return void
     *
     * @throws GeometryIOException
     */
    public function matchOpener() : void
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw new GeometryIOException("Expected '(' but encountered end of stream");
        }
        if ($token[1] !== '(') {
            throw new GeometryIOException("Expected '(' but encountered '" . $token[1] . "'");
        }
    }

    /**
     * @return void
     *
     * @throws GeometryIOException
     */
    public function matchCloser() : void
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw new GeometryIOException("Expected ')' but encountered end of stream");
        }
        if ($token[1] !== ')') {
            throw new GeometryIOException("Expected ')' but encountered '" . $token[1] . "'");
        }
    }

    /**
     * @return string
     *
     * @throws GeometryIOException
     */
    public function getNextWord() : string
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw new GeometryIOException("Expected word but encountered end of stream");
        }
        if ($token[0] !== static::T_WORD) {
            throw new GeometryIOException("Expected word but encountered '" . $token[1] . "'");
        }

        return $token[1];
    }

    /**
     * @return string|null The next word, or NULL if the next token is not a word, or there are no more tokens.
     */
    public function getOptionalNextWord() : ?string
    {
        $token = current($this->tokens);

        if ($token === false) {
            return null;
        }
        if ($token[0] !== static::T_WORD) {
            return null;
        }

        next($this->tokens);

        return $token[1];
    }

    /**
     * Returns whether the next token is an opener or a word.
     *
     * @return bool True if the next token is an opener, false if it is a word.
     *
     * @throws GeometryIOException If the next token is not an opener or a word, or if there is no next token.
     */
    public function isNextOpenerOrWord() : bool
    {
        $token = current($this->tokens);

        if ($token === false) {
            throw new GeometryIOException("Expected '(' or word but encountered end of stream");
        }

        if ($token[1] === '(') {
            return true;
        }

        if ($token[0] === static::T_WORD) {
            return false;
        }

        throw new GeometryIOException("Expected '(' or word but encountered '" . $token[1] . "'");
    }

    /**
     * @return float
     *
     * @throws GeometryIOException
     */
    public function getNextNumber() : float
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw new GeometryIOException("Expected number but encountered end of stream");
        }
        if ($token[0] !== static::T_NUMBER) {
            throw new GeometryIOException("Expected number but encountered '" . $token[1] . "'");
        }

        return (float) $token[1];
    }

    /**
     * @return string
     *
     * @throws GeometryIOException
     */
    public function getNextCloserOrComma() : string
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw new GeometryIOException("Expected ')' or ',' but encountered end of stream");
        }
        if ($token[1] !== ')' && $token[1] !== ',') {
            throw new GeometryIOException("Expected ')' or ',' but encountered '" . $token[1] . "'");
        }

        return $token[1];
    }

    /**
     * @return bool
     */
    public function isEndOfStream() : bool
    {
        return $this->nextToken() === null;
    }
}
