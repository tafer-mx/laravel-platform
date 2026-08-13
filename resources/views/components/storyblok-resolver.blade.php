@php($items = $items())

@if ($items === [])
    @include('components.storyblok._unknown')
@else
    @foreach ($items as $blok)
        @php($dynamicComponentName = $resolveDynamicComponent($blok))

        @if ($dynamicComponentName !== null)
            <x-dynamic-component
                :component="$dynamicComponentName"
                :blok="$blok"
                :global-config="$globalConfig"
            />
        @else
            @includeFirst($candidates($blok['component']), [
                'blok' => $blok,
                'globalConfig' => $globalConfig,
            ])
        @endif
    @endforeach
@endif
