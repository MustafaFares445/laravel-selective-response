<?php

namespace MustafaFares\SelectiveResponse\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class SelectiveResponseExtension extends OperationExtension
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        try {
            $methodNode = $routeInfo->methodNode();

            if (!$methodNode) {
                return;
            }

            $selectCalls = $this->findSelectCalls($methodNode);

            if (empty($selectCalls)) {
                return;
            }

            $selectedFields = $this->extractSelectedFields($selectCalls);

            if (empty($selectedFields)) {
                return;
            }

            $this->filterResponseSchema($operation, $selectedFields);
        } catch (\Throwable $e) {
            // Silently fail to not break documentation generation
        }
    }

    protected function findSelectCalls(Node $node): array
    {
        $nodeFinder = new NodeFinder();

        return $nodeFinder->find($node, function (Node $node) {
            if (!$node instanceof Node\Expr\MethodCall) {
                return false;
            }

            if (!$node->name instanceof Node\Identifier) {
                return false;
            }

            return $node->name->name === 'select';
        });
    }

    protected function extractSelectedFields(array $selectCalls): array
    {
        $allFields = [];

        foreach ($selectCalls as $call) {
            if (empty($call->args)) {
                continue;
            }

            $firstArg = $call->args[0]->value;

            if ($firstArg instanceof Node\Expr\Array_) {
                $fields = $this->extractFromArray($firstArg);
            } elseif ($firstArg instanceof Node\Scalar\String_) {
                $fields = [$firstArg->value];
            } elseif ($firstArg instanceof Node\Arg && $firstArg->value instanceof Node\Scalar\String_) {
                $fields = [$firstArg->value->value];
            } else {
                continue;
            }

            foreach ($fields as $field) {
                $field = $this->normalizeFieldName($field);
                if ($field) {
                    $allFields[] = $field;
                }
            }
        }

        return array_unique($allFields);
    }

    protected function extractFromArray(Node\Expr\Array_ $arrayNode): array
    {
        $fields = [];

        foreach ($arrayNode->items as $item) {
            if (!$item || !$item->value instanceof Node\Scalar\String_) {
                continue;
            }

            $fields[] = $item->value->value;
        }

        return $fields;
    }

    protected function normalizeFieldName(string $field): ?string
    {
        $field = trim($field);

        if (empty($field)) {
            return null;
        }

        if (preg_match('/^[\w.]+\.(\w+)$/', $field, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\w+)\s+as\s+(\w+)$/i', $field, $matches)) {
            return $matches[2];
        }

        if (preg_match('/^COUNT\(|SUM\(|AVG\(|MAX\(|MIN\(/i', $field)) {
            if (preg_match('/as\s+(\w+)$/i', $field, $matches)) {
                return $matches[1];
            }
            return null;
        }

        return $field;
    }

    protected function filterResponseSchema(Operation $operation, array $selectedFields): void
    {
        $responses = $operation->responses;

        foreach ($responses as $response) {
            $content = $response->content;

            foreach ($content as $contentItem) {
                $schema = $contentItem->schema;

                if (!$schema) {
                    continue;
                }

                $this->filterSchemaProperties($schema, $selectedFields);
            }
        }
    }

    protected function filterSchemaProperties($schema, array $selectedFields): void
    {
        if (!method_exists($schema, 'properties')) {
            return;
        }

        $properties = $schema->properties ?? [];

        if (empty($properties)) {
            return;
        }

        $filteredProperties = [];
        $required = $schema->required ?? [];

        foreach ($properties as $key => $property) {
            if (in_array($key, $selectedFields)) {
                $filteredProperties[$key] = $property;
            } elseif (in_array($key, $required)) {
                $required = array_filter($required, fn($r) => $r !== $key);
            }
        }

        if (method_exists($schema, 'setProperties')) {
            $schema->setProperties($filteredProperties);
        }

        if (method_exists($schema, 'setRequired') && !empty($required)) {
            $schema->setRequired(array_values($required));
        }

        if (method_exists($schema, 'setDescription')) {
            $fieldsList = implode(', ', $selectedFields);
            $description = "Partial response with selected fields: {$fieldsList}";
            $schema->setDescription($description);
        }

        if (method_exists($schema, 'items')) {
            $items = $schema->items;
            if ($items) {
                $this->filterSchemaProperties($items, $selectedFields);
            }
        }
    }
}

