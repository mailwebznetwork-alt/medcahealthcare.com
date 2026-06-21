<?php

use App\Models\Application;
use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\SectionLibraryItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\Vacancy;

return [

    'resources' => [

        'site_architect.pages' => [
            'model' => Page::class,
            'label' => 'Pages',
            'module' => 'site_architect',
            'actions' => ['delete', 'duplicate', 'publish', 'unpublish', 'export'],
            'destructive' => ['delete'],
            'inline_modify' => true,
        ],

        'site_architect.blogs' => [
            'model' => Blog::class,
            'label' => 'Blogs',
            'module' => 'site_architect',
            'actions' => ['delete', 'duplicate', 'publish', 'unpublish', 'export'],
            'destructive' => ['delete'],
            'inline_modify' => true,
        ],

        'site_architect.blocks' => [
            'model' => Block::class,
            'label' => 'Blocks',
            'module' => 'site_architect',
            'actions' => ['delete', 'duplicate', 'publish', 'unpublish', 'export', 'sync'],
            'destructive' => ['delete'],
            'inline_modify' => true,
        ],

        'site_architect.sections' => [
            'model' => SectionLibraryItem::class,
            'label' => 'Legacy Sections',
            'module' => 'site_architect',
            'actions' => ['delete', 'export'],
            'destructive' => ['delete'],
        ],

        'operations.services' => [
            'model' => Service::class,
            'label' => 'Services',
            'module' => 'operations',
            'actions' => ['delete', 'duplicate', 'modify'],
            'destructive' => ['delete'],
            'edit_route' => 'operations.services.edit',
        ],

        'operations.service_categories' => [
            'model' => ServiceCategory::class,
            'label' => 'Service Categories',
            'module' => 'operations',
            'actions' => ['delete', 'duplicate', 'modify'],
            'destructive' => ['delete'],
            'edit_route' => 'operations.service-categories.edit',
        ],

        'operations.sub_services' => [
            'model' => SubService::class,
            'label' => 'Sub-services',
            'module' => 'operations',
            'actions' => ['delete'],
            'destructive' => ['delete'],
        ],

        'operations.vacancies' => [
            'model' => Vacancy::class,
            'label' => 'Vacancies',
            'module' => 'operations',
            'actions' => ['delete', 'duplicate', 'modify'],
            'destructive' => ['delete'],
            'edit_route' => 'operations.job-portal.vacancies.edit',
        ],

        'operations.applications' => [
            'model' => Application::class,
            'label' => 'Applications',
            'module' => 'operations',
            'actions' => ['modify'],
            'edit_route' => 'operations.job-portal.applications.show',
        ],

        'operations.pin_codes' => [
            'model' => PinCode::class,
            'label' => 'Countrys',
            'module' => 'operations',
            'actions' => ['delete', 'modify', 'publish', 'unpublish'],
            'destructive' => ['delete'],
            'edit_route' => 'operations.pin-codes.edit',
        ],

    ],

];
