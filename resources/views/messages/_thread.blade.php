@php
    $meId     = (int) auth()->id();
    $grouped  = $messages->groupBy(fn ($m) => $m->created_at->toDateString());
    $lastMine = (int) $messages->where('sender_id', $meId)->max('id');
@endphp

@forelse($grouped as $day => $items)
    @php
        $date  = \Carbon\Carbon::parse($day);
        $label = $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('d M Y'));
    @endphp

    <div class="day-sep"><span>{{ $label }}</span></div>

    @foreach($items as $m)
        @php($mine = (int) $m->sender_id === $meId)
        <div class="bubble {{ $mine ? 'bubble-me' : 'bubble-them' }}">
            @if($m->student)
                <div class="mb-1 text-xs font-semibold opacity-75">About {{ $m->student->first_name }} {{ $m->student->last_name }}</div>
            @endif

            @if($m->body !== null && $m->body !== '')
                <div class="whitespace-pre-line break-words">{{ $m->body }}</div>
            @endif

            <x-attachment-list :attachments="$m->attachments" class="{{ $m->body ? 'mt-2' : '' }}" />

            <div class="mt-1 flex items-center justify-between gap-4 text-[11px] opacity-75">
                <span>
                    {{ $m->created_at->format('H:i') }}
                    @if($mine && (int) $m->id === $lastMine) · {{ $m->is_read ? 'Seen' : 'Sent' }} @endif
                </span>
                <form action="{{ route('messages.destroy', $m) }}" method="POST" class="bubble-actions"
                      onsubmit="return confirm('Remove this message from your view? The school can still retrieve it.')">
                    @csrf
                    @method('DELETE')
                    <button class="underline">Remove</button>
                </form>
            </div>
        </div>
    @endforeach
@empty
    <x-empty title="No messages yet" text="Say hello below." icon="chat" />
@endforelse