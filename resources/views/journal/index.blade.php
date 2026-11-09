<x-layouts::app :title="__('Journal')">
    <section class="page-heading"><p class="planner-muted">{{ __('A private note for your day') }}</p><h1>{{ __('Journal') }}</h1></section>
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    <form class="planner-form-card journal-form" method="POST" action="{{ route('journal.store') }}">
        @csrf
        <label class="planner-field"><span>{{ __('Date') }}</span><input type="date" name="date" value="{{ old('date', $date) }}" max="{{ now(auth()->user()->timezone)->toDateString() }}" required>@error('date')<small>{{ $message }}</small>@enderror</label>
        <fieldset class="planner-mood-options"><legend>{{ __('How are you feeling?') }}</legend><div>
            @foreach ([1 => 'Very low', 2 => 'Low', 3 => 'Okay', 4 => 'Good', 5 => 'Great'] as $value => $label)
                <label><input type="radio" name="mood" value="{{ $value }}" @checked((int) old('mood', $entry?->mood ?? 3) === $value)><span>{{ $label }}</span></label>
            @endforeach
        </div>@error('mood')<small>{{ $message }}</small>@enderror</fieldset>
        <label class="planner-field"><span>{{ __('Journal entry') }}</span><textarea name="body" rows="10" maxlength="20000" required placeholder="{{ __('Write a few lines about your day…') }}">{{ old('body', $entry?->body) }}</textarea><small>{{ __('Your entry is encrypted before it is stored.') }}</small>@error('body')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-actions"><button class="planner-button planner-button-primary" type="submit">{{ $entry ? __('Update entry') : __('Save entry') }}</button><a class="planner-button planner-button-outline" href="{{ route('calendar', ['month' => substr($date, 0, 7), 'day' => $date]) }}">{{ __('Back to this day') }}</a></div>
    </form>
</x-layouts::app>
