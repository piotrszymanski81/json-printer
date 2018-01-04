<?php

declare(strict_types=1);

/**
 * Copyright (c) 2018 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/json-printer
 */

namespace Localheinz\Json\Printer;

final class Printer
{
    /**
     * This code is adopted from composer/composer (originally licensed under MIT by Nils Adermann <naderman@naderman.de>
     * and Jordi Boggiano <j.boggiano@seld.be>), who adopted it from a blog post by Dave Perrett (originally licensed
     * under MIT by Dave Perrett <mail@recursive-design.com>).
     *
     * @see https://github.com/composer/composer/blob/1.6.0/src/Composer/Json/JsonFormatter.php#L25-L126
     * @see https://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
     *
     * @param string $original
     * @param bool   $unEscapeUnicode
     * @param bool   $unEscapeSlashes
     *
     * @return string
     */
    public function print(string $original, bool $unEscapeUnicode, bool $unEscapeSlashes): string
    {
        $printed = '';
        $indentLevel = 0;
        $length = \strlen($original);
        $indent = '    ';
        $newLine = "\n";
        $withinStringLiteral = false;
        $stringLiteral = '';
        $noEscape = true;

        for ($i = 0; $i < $length; ++$i) {
            // Grab the next character in the string
            $character = \substr($original, $i, 1);

            // Are we inside a quoted string?
            if ('"' === $character && $noEscape) {
                $withinStringLiteral = !$withinStringLiteral;
            }

            if ($withinStringLiteral) {
                $stringLiteral .= $character;
                $noEscape = '\\' === $character ? !$noEscape : true;

                continue;
            }

            if ('' !== $stringLiteral) {
                if ($unEscapeSlashes) {
                    $stringLiteral = \str_replace('\\/', '/', $stringLiteral);
                }

                if ($unEscapeUnicode && \function_exists('mb_convert_encoding')) {
                    // https://stackoverflow.com/questions/2934563/how-to-decode-unicode-escape-sequences-like-u00ed-to-proper-utf-8-encoded-cha
                    $stringLiteral = \preg_replace_callback('/(\\\\+)u([0-9a-f]{4})/i', function (array $match) {
                        $length = \strlen($match[1]);

                        if ($length % 2) {
                            return \str_repeat('\\', $length - 1) . \mb_convert_encoding(
                                \pack('H*', $match[2]),
                                'UTF-8',
                                'UCS-2BE'
                            );
                        }

                        return $match[0];
                    }, $stringLiteral);
                }

                $printed .= $stringLiteral . $character;
                $stringLiteral = '';

                continue;
            }

            if (':' === $character) {
                // Add a space after the : character
                $character .= ' ';
            } elseif (('}' === $character || ']' === $character)) {
                --$indentLevel;
                $previousCharacter = \substr($original, $i - 1, 1);

                if ('{' !== $previousCharacter && '[' !== $previousCharacter) {
                    // If this character is the end of an element,
                    // output a new line and indent the next line
                    $printed .= $newLine;

                    for ($j = 0; $j < $indentLevel; ++$j) {
                        $printed .= $indent;
                    }
                } else {
                    // Collapse empty {} and []
                    $printed = \rtrim($printed);
                }
            }

            $printed .= $character;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            if (',' === $character || '{' === $character || '[' === $character) {
                $printed .= $newLine;

                if ('{' === $character || '[' === $character) {
                    ++$indentLevel;
                }

                for ($j = 0; $j < $indentLevel; ++$j) {
                    $printed .= $indent;
                }
            }
        }

        return $printed;
    }
}