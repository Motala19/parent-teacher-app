@props(['always' => false])

<div x-data="installApp({{ $always ? 'true' : 'false' }})" x-show="show" x-transition style="display:none"
     {{ $attributes->merge(['class' => 'card note tone-event p-4']) }}>
    <div class="flex items-start gap-3">
        <x-application-logo class="h-11 w-11 shrink-0" />

        <div class="min-w-0 flex-1">
            <div class="font-bold text-ink">Install the {{ config('app.name') }} app</div>

            <p class="mt-0.5 text-sm text-ink-muted" x-show="! ios">
                Add it to your home screen for one-tap access to announcements and messages.
            </p>
            <p class="mt-0.5 text-sm text-ink-muted" x-show="ios" style="display:none">
                Tap the <strong>Share</strong> button at the bottom of Safari, then choose <strong>Add to Home Screen</strong>.
            </p>

            <div class="mt-3 flex gap-2" x-show="! ios">
                <button type="button" class="btn btn-primary btn-sm" x-on:click="install()">Install</button>
                <button type="button" class="btn btn-ghost btn-sm" x-show="! always" x-on:click="dismiss()">Not now</button>
            </div>

            <div class="mt-3" x-show="ios" style="display:none">
                <button type="button" class="btn btn-ghost btn-sm" x-show="! always" x-on:click="dismiss()">Got it</button>
            </div>
        </div>
    </div>
</div>