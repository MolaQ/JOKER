@props(['selected' => '', 'id' => 'team_id'])
{{-- Drużyna albo grupa minisiatkówki przy zakładaniu konta (App\Support\RequestedGroup). --}}
@php ['teams' => $teams, 'mini' => $mini] = \App\Support\RequestedGroup::options(); @endphp
<select id="{{ $id }}" name="team_id" {{ $attributes }}>
    <option value="">{{ __('Wybierz drużynę lub grupę') }}</option>
    @if($mini->isNotEmpty())
        <optgroup label="{{ __('Drużyny') }}">
    @endif
    @foreach($teams as $team)
        <option value="{{ $team->id }}" @selected((string) $selected === (string) $team->id)>{{ $team->name }}</option>
    @endforeach
    @if($mini->isNotEmpty())
        </optgroup>
        <optgroup label="{{ __('Minisiatkówka') }}">
            @foreach($mini as $group)
                <option value="{{ \App\Support\RequestedGroup::MINI_PREFIX.$group->id }}" @selected((string) $selected === \App\Support\RequestedGroup::MINI_PREFIX.$group->id)>{{ __('Minisiatkówka') }}: {{ $group->name }}</option>
            @endforeach
        </optgroup>
    @endif
</select>
