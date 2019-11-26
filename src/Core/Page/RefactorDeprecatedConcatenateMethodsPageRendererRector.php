<?php

declare(strict_types=1);

namespace Ssch\TYPO3Rector\Core\Page;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;
use TYPO3\CMS\Core\Page\PageRenderer;

final class RefactorDeprecatedConcatenateMethodsPageRendererRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param Node|MethodCall $node
     *
     * @return Node|null
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node, PageRenderer::class)) {
            return null;
        }

        if (!$this->isNames($node, ['getConcatenateFiles', 'enableConcatenateFiles', 'disableConcatenateFiles'])) {
            return null;
        }

        if ($this->isName($node, 'getConcatenateFiles')) {
            return $this->createArrayMergeCall($node);
        }

        if ($this->isName($node, 'enableConcatenateFiles')) {
            return $this->splitMethodCall($node, 'enableConcatenateJavascript', 'enableConcatenateCss');
        }

        if ($this->isName($node, 'disableConcatenateFiles')) {
            return $this->splitMethodCall($node, 'disableConcatenateJavascript', 'disableConcatenateCss');
        }

        return null;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns method call names to new ones.', [
            new ConfiguredCodeSample(
                <<<'PHP'
$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
$files = $someObject->getConcatenateFiles();
PHP
                ,
                <<<'PHP'
$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
$files = array_merge($this->getConcatenateCss(), $this->getConcatenateJavascript());
PHP
            ),
        ]);
    }

    private function createArrayMergeCall(MethodCall $node): FuncCall
    {
        $node1 = clone $node;
        $node2 = clone $node;
        $node1->name = new Identifier('getConcatenateCss');
        $node2->name = new Identifier('getConcatenateJavascript');

        return new FuncCall(new Name('array_merge'), [
            new Arg($node1),
            new Arg($node2),
        ]);
    }

    /**
     * @param MethodCall $node
     * @param string $firstMethod
     * @param string $secondMethod
     *
     * @return Node
     */
    private function splitMethodCall(MethodCall $node, string $firstMethod, string $secondMethod): Node
    {
        $node->name = new Identifier($firstMethod);
        $node1 = clone $node;
        $node1->name = new Identifier($secondMethod);
        $this->addNodeAfterNode($node1, $node);

        return $node;
    }
}