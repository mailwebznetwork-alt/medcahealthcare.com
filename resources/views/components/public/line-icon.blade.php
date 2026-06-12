@props([
    'name' => null,
    'model' => null,
    'size' => 'md',
    'label' => null,
])

@php
    use App\Services\Public\CatalogLineIconResolver;
    use App\Services\Public\LucideSvgRenderer;
    use Illuminate\Database\Eloquent\Model;

    $resolver = app(CatalogLineIconResolver::class);
    $renderer = app(LucideSvgRenderer::class);

    if (filled($name)) {
        $iconName = (string) $name;
    } elseif ($model instanceof Model) {
        $iconName = $resolver->iconNameFor($model);
    } else {
        $iconName = 'circle';
    }

    $sizeClass = match ($size) {
        'xs' => 'medca-line-icon--xs',
        'sm' => 'medca-line-icon--sm',
        'lg' => 'medca-line-icon--lg',
        'xl' => 'medca-line-icon--xl',
        default => 'medca-line-icon--md',
    };
@endphp

<span {{ $attributes->class(['medca-line-icon-wrap inline-flex shrink-0 items-center justify-center']) }}>
    {!! $renderer->svg($iconName, $sizeClass, $label) !!}
</span>
