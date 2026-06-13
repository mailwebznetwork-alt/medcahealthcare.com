<?php

namespace App\GraphQL;

use App\Models\PinCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

class CatalogGraphqlExecutor
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $query, ?array $variables = null): array
    {
        $result = GraphQL::executeQuery($this->schema(), $query, null, null, $variables ?? []);

        return $result->toArray();
    }

    public function schema(): Schema
    {
        $serviceType = new ObjectType([
            'name' => 'Service',
            'fields' => [
                'code' => Type::nonNull(Type::string()),
                'title' => Type::string(),
                'url' => Type::string(),
                'quickAnswer' => Type::string(),
                'aiSummary' => Type::string(),
            ],
        ]);

        $pinType = new ObjectType([
            'name' => 'PinCode',
            'fields' => [
                'pincode' => Type::nonNull(Type::string()),
                'areaName' => Type::string(),
                'city' => Type::string(),
            ],
        ]);

        $categoryType = new ObjectType([
            'name' => 'Category',
            'fields' => [
                'code' => Type::nonNull(Type::string()),
                'name' => Type::string(),
                'url' => Type::string(),
            ],
        ]);

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'services' => [
                    'type' => Type::listOf($serviceType),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => function ($root, array $args): array {
                        $limit = min((int) ($args['limit'] ?? 50), 100);

                        return Service::query()
                            ->where('is_active', true)
                            ->orderBy('service_code')
                            ->limit($limit)
                            ->get()
                            ->map(fn (Service $s): array => [
                                'code' => $s->service_code,
                                'title' => $s->title,
                                'url' => $s->publicUrl(),
                                'quickAnswer' => $s->quick_answer,
                                'aiSummary' => $s->ai_summary,
                            ])
                            ->all();
                    },
                ],
                'service' => [
                    'type' => $serviceType,
                    'args' => [
                        'code' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => function ($root, array $args): ?array {
                        $s = Service::query()->where('service_code', $args['code'])->where('is_active', true)->first();
                        if ($s === null) {
                            return null;
                        }

                        return [
                            'code' => $s->service_code,
                            'title' => $s->title,
                            'url' => $s->publicUrl(),
                            'quickAnswer' => $s->quick_answer,
                            'aiSummary' => $s->ai_summary,
                        ];
                    },
                ],
                'pincodes' => [
                    'type' => Type::listOf($pinType),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => function ($root, array $args): array {
                        $limit = min((int) ($args['limit'] ?? 100), 250);

                        return PinCode::query()
                            ->where('is_active', true)
                            ->orderBy('pincode')
                            ->limit($limit)
                            ->get()
                            ->map(fn (PinCode $p): array => [
                                'pincode' => $p->pincode,
                                'areaName' => $p->area_name,
                                'city' => $p->city,
                            ])
                            ->all();
                    },
                ],
                'categories' => [
                    'type' => Type::listOf($categoryType),
                    'args' => [
                        'limit' => Type::int(),
                    ],
                    'resolve' => function ($root, array $args): array {
                        $limit = min((int) ($args['limit'] ?? 50), 100);

                        return ServiceCategory::query()
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->limit($limit)
                            ->get()
                            ->map(fn (ServiceCategory $c): array => [
                                'code' => $c->code,
                                'name' => $c->name,
                                'url' => $c->publicUrl(),
                            ])
                            ->all();
                    },
                ],
            ],
        ]);

        return new Schema(['query' => $queryType]);
    }
}
