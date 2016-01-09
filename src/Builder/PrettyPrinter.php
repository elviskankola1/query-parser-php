<?php

namespace Gdbots\QueryParser\Builder;

use Gdbots\QueryParser\Enum\ComparisonOperator;
use Gdbots\QueryParser\Node\Date;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Hashtag;
use Gdbots\QueryParser\Node\Mention;
use Gdbots\QueryParser\Node\Node;
use Gdbots\QueryParser\Node\Number;
use Gdbots\QueryParser\Node\Phrase;
use Gdbots\QueryParser\Node\Range;
use Gdbots\QueryParser\Node\Subquery;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;

class PrettyPrinter extends AbstractQueryBuilder
{
    /** @var string */
    protected $result;

    /**
     * @return string
     */
    public function getResult()
    {
        return trim($this->result);
    }

    /**
     * @param ParsedQuery $parsedQuery
     */
    protected function beforeAddParsedQuery(ParsedQuery $parsedQuery)
    {
        $this->result = '';
    }

    /**
     * @param Node $node
     */
    protected function printPrefix(Node $node)
    {
        if ($node->isRequired()) {
            $this->result .= '+';
        } elseif ($node->isProhibited()) {
            $this->result .= '-';
        }
    }

    /**
     * @param Node $node
     */
    protected function printPostfix(Node $node)
    {
        if ($node instanceof Word && $node->hasTrailingWildcard()) {
            $this->result .= '*';
        }

        if ($node->useBoost()) {
            $this->result .= '^'.$node->getBoost();
        } elseif ($node->useFuzzy()) {
            $this->result .= '~'.$node->getFuzzy();
        }

        if (!$this->inRange) {
            $this->result .= ' ';
        }
    }

    /**
     * @param Node $node
     */
    protected function handleTerm(Node $node)
    {
        $this->printPrefix($node);
        $this->result .= $node instanceof Phrase ? '"'.$node->getValue().'"' : $node->getValue();
        if ($this->inField && !$this->inRange && !$this->inSubquery) {
            return;
        }
        $this->printPostfix($node);
    }

    /**
     * @param Node $node
     */
    protected function handleExplicitTerm(Node $node)
    {
        $this->printPrefix($node);
        if ($node instanceof Hashtag) {
            $this->result .= '#';
        } elseif ($node instanceof Mention) {
            $this->result .= '@';
        }
        $this->result .= $node->getValue();
        if ($this->inField && !$this->inRange && !$this->inSubquery) {
            return;
        }
        $this->printPostfix($node);
    }

    /**
     * @param Node $node
     */
    protected function handleNumericTerm(Node $node)
    {
        $this->printPrefix($node);

        if ($node instanceof Number || $node instanceof Date) {
            switch ($node->getComparisonOperator()->getValue()) {
                case ComparisonOperator::GT:
                    $this->result .= '>';
                    break;

                case ComparisonOperator::GTE:
                    $this->result .= '>=';
                    break;

                case ComparisonOperator::LT:
                    $this->result .= '<';
                    break;

                case ComparisonOperator::LTE:
                    $this->result .= '<=';
                    break;

                default:
                    break;

            }
        }

        $this->result .= $node->getValue();
        if ($this->inField && !$this->inRange && !$this->inSubquery) {
            return;
        }
        $this->printPostfix($node);
    }

    /**
     * @param Field $field
     */
    protected function startField(Field $field)
    {
        $this->printPrefix($field);
        $this->result .= $field->getName().':';
    }

    /**
     * @param Field $field
     */
    protected function endField(Field $field)
    {
        $this->printPostfix($field);
    }

    /**
     * @param Range $range
     */
    protected function handleRange(Range $range)
    {
        $this->printPrefix($range);
        $this->result .= $range->isExclusive() ? '{' : '[';

        if ($range->hasLowerNode()) {
            $range->getLowerNode()->acceptBuilder($this);
        } else {
            $this->result .= '*';
        }

        $this->result .= '..';

        if ($range->hasUpperNode()) {
            $range->getUpperNode()->acceptBuilder($this);
        } else {
            $this->result .= '*';
        }

        $this->result .= $range->isExclusive() ? '}' : ']';
        $this->printPostfix($range);
    }

    /**
     * @param Subquery $subquery
     */
    protected function startSubquery(Subquery $subquery)
    {
        $this->printPrefix($subquery);
        $this->result .= '(';
    }

    /**
     * @param Subquery $subquery
     */
    protected function endSubquery(Subquery $subquery)
    {
        $this->result = trim($this->result).')';
        $this->printPostfix($subquery);
    }

    /**
     * @param Node $node
     */
    protected function mustMatchText(Node $node)
    {
        $this->result .= $node->getValue().' ';
    }

    /**
     * @param Node $node
     */
    protected function shouldMatchText(Node $node)
    {
        $this->result .= $node->getValue().' ';
    }

    /**
     * @param Node $node
     */
    protected function mustNotMatchText(Node $node)
    {
        $this->result .= $node->getValue().' ';
    }

    /**
     * @param Node $node
     */
    protected function mustMatchTerm(Node $node)
    {
    }

    /**
     * @param Node $node
     */
    protected function shouldMatchTerm(Node $node)
    {
    }

    /**
     * @param Node $node
     */
    protected function mustNotMatchTerm(Node $node)
    {
    }
}