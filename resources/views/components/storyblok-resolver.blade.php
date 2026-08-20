@php
    $items = $items();
@endphp

@if ($items === [])
    @include('components.storyblok._unknown')
@else
    @foreach ($items as $blok)
        @php
            /*
             * El contexto se obtiene únicamente desde el stack.
             *
             * Si este blok tiene context_relation, enterContext()
             * resolverá la relación y agregará el nuevo contexto.
             *
             * Si no tiene context_relation, conservará el contexto
             * actual y devolverá false.
             */
            $createdContext = $enterContext($blok);

            $dynamicComponentName = $resolveDynamicComponent($blok);
        @endphp

        @if ($dynamicComponentName !== null)
            <x-dynamic-component
                :component="$dynamicComponentName"
                :blok="$blok"
                :global-config="$globalConfig"
                :tafer_rewards_mode="$tafer_rewards_mode"
                :footer_mode="$footer_mode"
                :preserve_original_colors="$preserve_original_colors"
                :footer_logo="$footer_logo"
                :mobile_full_width="$mobile_full_width"
            />
        @else
            @includeFirst($candidates($blok['component']), [
                'blok' => $blok,
                'globalConfig' => $globalConfig,
                'tafer_rewards_mode' => $tafer_rewards_mode,
                'footer_mode' => $footer_mode,
                'preserve_original_colors' => $preserve_original_colors,
                'footer_logo' => $footer_logo,
                'mobile_full_width' => $mobile_full_width,
            ])
        @endif

        @php
            $leaveContext($createdContext);
        @endphp
    @endforeach
@endif