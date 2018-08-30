@each('partials.component-group', $component_groups, 'componentGroup')

@if($ungrouped_components->count() > 0)
<ul class="list-group components">
    <li class="list-group-item group-name">
        <strong>{{ trans('cachet.components.group.other') }}</strong>
    </li>
    @foreach($ungrouped_components as $component)
    @include('partials.component', compact($component))
    @endforeach
</ul>
@endif
