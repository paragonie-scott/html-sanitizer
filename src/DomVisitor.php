<?php

/*
 * This file is part of the HTML sanitizer project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HtmlSanitizer;

use HtmlSanitizer\Model\Cursor;
use HtmlSanitizer\Node\DocumentNode;
use HtmlSanitizer\Node\TextNode;
use HtmlSanitizer\Visitor\NodeVisitorInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class DomVisitor implements DomVisitorInterface
{
    /**
     * @var NodeVisitorInterface[]
     */
    private $visitors;

    /**
     * @var NodeVisitorInterface[]
     */
    private $reversedVisitors;

    /**
     * @param NodeVisitorInterface[] $visitors
     */
    public function __construct(array $visitors = [])
    {
        $this->visitors = $visitors;
        $this->reversedVisitors = [];
    }

    public function visit(\DOMNode $node): DocumentNode
    {
        if (!$this->reversedVisitors) {
            $this->reversedVisitors = array_reverse($this->visitors);
        }

        $cursor = new Cursor();
        $cursor->node = new DocumentNode();

        $this->visitNode($node, $cursor);

        return $cursor->node;
    }

    private function visitNode(\DOMNode $node, Cursor $cursor)
    {
        foreach ($this->visitors as $visitor) {
            if ($visitor->supports($node, $cursor)) {
                $visitor->enterNode($node, $cursor);
            }
        }

        /** @var \DOMNode $child */
        foreach ($node->childNodes ?? [] as $k => $child) {
            if ('#text' === $child->nodeName) {
                // Add text in the safe tree without a visitor for performance
                $cursor->node->addChild(new TextNode($cursor->node, $child->nodeValue));
            } elseif (!$child instanceof \DOMText) { // Ignore HTML comments
                $this->visitNode($child, $cursor);
            }
        }

        foreach ($this->reversedVisitors as $visitor) {
            if ($visitor->supports($node, $cursor)) {
                $visitor->leaveNode($node, $cursor);
            }
        }
    }
}
