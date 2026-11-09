@if($c = session('conflict'))
    <div class="mb-6 rounded-2xl border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-900">

        @if($c['type'] === 'siblings')
            <div class="font-bold">Please check these learners</div>

            <p class="mt-1">
                <strong>{{ $c['parent'] }}</strong> would be linked to
                <strong>{{ implode(', ', $c['learners']) }}</strong>,
                @if(! empty($c['others']))
                    who {{ count($c['learners']) > 1 ? 'do' : 'does' }} not share a surname or a parent with
                    {{ implode(', ', $c['others']) }}.
                @else
                    who are not brothers and sisters of each other.
                @endif
                A parent can only be linked to learners who are siblings.
            </p>

            <label class="mt-3 flex items-center gap-2 font-semibold">
                <input type="checkbox" name="confirm_siblings" value="1"
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                Yes, they are brothers and sisters (for example, different surnames in a blended family)
            </label>
            <p class="mt-1 text-xs">Tick the box, then press the button again.</p>
        @endif

        @if($c['type'] === 'duplicate')
            <div class="font-bold">A parent called {{ $c['name'] }} is already in the system</div>
            <p class="mt-1">This looks like the same person entered with different contact details. Choose one:</p>

            <div class="mt-3 space-y-2">
                @foreach($c['parents'] as $p)
                    <label class="flex items-start gap-2">
                        <input type="radio" name="duplicate_choice" value="existing:{{ $p['id'] }}" {{ $loop->first ? 'checked' : '' }}
                               class="mt-1 border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <strong>Link the existing parent</strong>
                            ({{ $p['phone'] ?: 'no phone' }} · {{ $p['email'] ?: 'no email' }})
                            @if($p['children'])<br><span class="text-xs">Already the parent of {{ implode(', ', $p['children']) }}</span>@endif
                        </span>
                    </label>
                @endforeach

                <label class="flex items-start gap-2">
                    <input type="radio" name="duplicate_choice" value="new"
                           class="mt-1 border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span><strong>This is a different person</strong> with the same name. Create a new parent.</span>
                </label>
            </div>
            <p class="mt-2 text-xs">Make your choice, then press the button again.</p>
        @endif
    </div>
@endif