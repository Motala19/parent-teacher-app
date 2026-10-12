<x-app-layout>
    <x-slot name="header">
        <h2>Notification settings</h2>
        <p>Choose how the school reaches you.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-4">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="p-6 sm:p-8">
                    <form action="{{ route('notifications.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <h3 class="font-bold text-ink mb-1">Email notifications</h3>
                        <p class="text-sm text-ink-muted mb-5">
                            @if($user->realEmail())
                                Emails go to <strong class="text-ink">{{ $user->realEmail() }}</strong>.
                            @else
                                You don't have an email address on your account, so you'll only get notifications here in the app. Ask your school to add one if you'd like emails too.
                            @endif
                        </p>

                        <input type="hidden" name="email_notifications" value="0">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="email_notifications" value="1"
                                   {{ old('email_notifications', $user->email_notifications ?? true) ? 'checked' : '' }}
                                   {{ $user->realEmail() ? '' : 'disabled' }}
                                   class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span>
                                <span class="block font-semibold text-ink">Email me about new messages and announcements</span>
                                <span class="block text-sm text-ink-muted">Newsletters, homework, assignments, question papers and urgent notices.</span>
                            </span>
                        </label>

                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary"><x-icon name="check" :size="16" /> Save settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="p-6 sm:p-8 text-sm text-ink-soft space-y-3">
                    <h3 class="font-bold text-ink">How it works</h3>
                    <p><strong>In the app:</strong> the bell at the top always shows new messages and announcements, even if emails are off.</p>
                    <p><strong>Messages:</strong> you get one email per person at most every 30 minutes, so a busy chat won't fill your inbox. For privacy, emails never include the message itself.</p>
                    <p><strong>Files:</strong> attachments are never sent by email. Log in to view or download them.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>