<?php

declare(strict_types=1);

namespace Ssch\TYPO3Rector\Rector\v8\v6;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use Rector\Core\Rector\AbstractRector;
use Ssch\TYPO3Rector\Helper\TcaHelperTrait;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/8.6/Deprecation-79440-TcaChanges.html
 */
final class AddTypeToColumnConfigRector extends AbstractRector
{
    use TcaHelperTrait;

    /**
     * @var string
     */
    private const TYPE = 'type';

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    /**
     * @param Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isTca($node)) {
            return null;
        }

        $columns = $this->extractColumns($node);

        if (! $columns instanceof ArrayItem) {
            return null;
        }

        $columnItems = $columns->value;

        if (! $columnItems instanceof Array_) {
            return null;
        }

        foreach ($columnItems->items as $fieldValue) {
            if (! $fieldValue instanceof ArrayItem) {
                continue;
            }

            if (null === $fieldValue->key) {
                continue;
            }

            if (! $fieldValue->value instanceof Array_) {
                continue;
            }

            $configArray = $fieldValue->value;
            $addConfig = true;
            $newConfiguration = new ArrayItem($this->createArray([
                self::TYPE => 'none',
            ]), new String_('config'));

            foreach ($fieldValue->value->items as $configValue) {
                if (null === $configValue) {
                    continue;
                }

                if (null === $configValue->key) {
                    continue;
                }

                if (! $this->isValue($configValue->key, 'config')) {
                    continue;
                }

                $newConfiguration = new ArrayItem($this->createArray([
                    self::TYPE => 'none',
                ]));

                $configArray = $configValue->value;

                if (! $configValue->value instanceof Array_) {
                    continue;
                }

                foreach ($configValue->value->items as $configItemValue) {
                    if (! $configItemValue instanceof ArrayItem) {
                        continue;
                    }

                    if (null === $configItemValue->key) {
                        continue;
                    }

                    if (! $this->isValue($configItemValue->key, self::TYPE)) {
                        continue;
                    }

                    $addConfig = false;
                }
            }

            if (! $addConfig) {
                continue;
            }

            if ($configArray instanceof Array_) {
                $configArray->items[] = $newConfiguration;
            }
        }

        return $node;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add type to column config if not exists', [new CodeSample(<<<'PHP'
return [
    'columns' => [
        'bar' => []
    ]
];
PHP
            , <<<'PHP'
return [
    'columns' => [
        'bar' => [
            'config' => [
                'type' => 'none'
            ]
        ]
    ]
];
PHP
        )]);
    }
}
