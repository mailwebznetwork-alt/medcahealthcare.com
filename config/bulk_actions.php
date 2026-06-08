<?php

use App\Models\Block;
use App\Models\Blog;
use App\Models\Page;
use App\Models\SectionLibraryItem;

return [

    'resources' => [

        'site_architect.pages' => [
            'model' => Page::class,
            'label' => 'Pages',
            'actions' => ['delete', 'publish', 'unpublish', 'export'],
            'destructive' => ['delete'],
        ],

        'site_architect.blogs' => [
            'model' => Blog::class,
            'label' => 'Blogs',
            'actions' => ['delete', 'publish', 'unpublish', 'export'],
            'destructive' => ['delete'],
        ],

        'site_architect.blocks' => [
            'model' => Block::class,
            'label' => 'Blocks',
            'actions' => ['delete', 'publish', 'unpublish', 'export', 'sync'],
            'destructive' => ['delete'],
        ],

        'site_architect.sections' => [
            'model' => SectionLibraryItem::class,
            'label' => 'Legacy Sections',
            'actions' => ['delete', 'export'],
            'destructive' => ['delete'],
        ],

    ],

];
