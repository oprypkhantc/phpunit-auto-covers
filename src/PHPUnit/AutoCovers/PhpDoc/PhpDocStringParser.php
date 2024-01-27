<?php

namespace Tests\PHPUnit\AutoCovers\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Reflector;

class PhpDocStringParser
{
	public function __construct(
		private readonly Lexer $lexer,
		private readonly PhpDocParser $phpDocParser
	) {}

	public function parse(Reflector $input): PhpDocNode
	{
		$input = $input->getDocComment() ?: null;

		if (!$input) {
			return new PhpDocNode([]);
		}

		$tokens = new TokenIterator($this->lexer->tokenize($input));

		return $this->phpDocParser->parse($tokens);
	}
}
