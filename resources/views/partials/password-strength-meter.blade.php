{{--
    Expects:
      $passwordField   - id of the password <input> this meter tracks (required)
      $usernameField   - id of a live username <input> on the same form, to
                          check "must not contain username" against (optional)
      $usernameValue   - a known, fixed username to check against instead,
                          e.g. the already-authenticated user's own (optional)
    Neither $usernameField nor $usernameValue given -> the "must not contain
    username" row is left out entirely (nothing to check it against).
--}}
@php
    $hasUsernameCheck = ($usernameField ?? null) || ($usernameValue ?? null);
@endphp
<div class="mt-2" data-password-strength
     data-password-field="{{ $passwordField }}"
     @if ($usernameField ?? null) data-username-field="{{ $usernameField }}" @endif
     @if ($usernameValue ?? null) data-username-value="{{ $usernameValue }}" @endif>
    <div class="flex gap-1.5 mb-3">
        @for ($i = 0; $i < 4; $i++)
            <span class="pw-strength-seg h-1.5 flex-1 rounded-full bg-[#141a2a] transition-colors duration-150"></span>
        @endfor
    </div>
    <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
        @foreach ([
            'length' => __('At least 8 characters'),
            'uppercase' => __('Uppercase letter'),
            'lowercase' => __('Lowercase letter'),
            'number' => __('Number'),
            'norepeat' => __('No 3 identical in a row'),
            'nosequential' => __('No sequential (abc, 123)'),
        ] as $rule => $label)
            <span class="pw-req flex items-center gap-1.5" data-rule="{{ $rule }}">
                <svg class="pw-req-dot w-3.5 h-3.5 shrink-0 text-[#4b4536]" viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="4"/></svg>
                <svg class="pw-req-check hidden w-3.5 h-3.5 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.4 7.4a1 1 0 01-1.4 0L3.3 9.5a1 1 0 111.4-1.4l3.9 3.9 6.7-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                <span class="pw-req-label text-[#9c8f7b]">{{ $label }}</span>
            </span>
        @endforeach
        @if ($hasUsernameCheck)
            <span class="pw-req flex items-center gap-1.5" data-rule="nousername">
                <svg class="pw-req-dot w-3.5 h-3.5 shrink-0 text-[#4b4536]" viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="4"/></svg>
                <svg class="pw-req-check hidden w-3.5 h-3.5 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.4 7.4a1 1 0 01-1.4 0L3.3 9.5a1 1 0 111.4-1.4l3.9 3.9 6.7-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                <span class="pw-req-label text-[#9c8f7b]">{{ __('Must not contain username') }}</span>
            </span>
        @endif
        <span class="pw-req flex items-center gap-1.5" data-rule="symbol">
            <svg class="pw-req-dot w-3.5 h-3.5 shrink-0 text-[#4b4536]" viewBox="0 0 20 20" fill="currentColor"><circle cx="10" cy="10" r="4"/></svg>
            <svg class="pw-req-check hidden w-3.5 h-3.5 shrink-0 text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.4 7.4a1 1 0 01-1.4 0L3.3 9.5a1 1 0 111.4-1.4l3.9 3.9 6.7-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
            <span class="pw-req-label text-[#9c8f7b]">{{ __('Symbol (optional)') }}</span>
        </span>
    </div>
</div>

@once
    @push('scripts')
    <script>
        (function () {
            const SEQ_COLORS = ['#dc2626', '#f59e0b', '#eab308', '#22c55e'];

            function hasSequentialRun(value) {
                const s = value.toLowerCase();
                for (let i = 0; i < s.length - 2; i++) {
                    const a = s.charCodeAt(i), b = s.charCodeAt(i + 1), c = s.charCodeAt(i + 2);
                    if ((b === a + 1 && c === b + 1) || (b === a - 1 && c === b - 1)) return true;
                }
                return false;
            }

            const RULES = {
                length: (v) => v.length >= 8,
                uppercase: (v) => /[A-Z]/.test(v),
                lowercase: (v) => /[a-z]/.test(v),
                number: (v) => /[0-9]/.test(v),
                norepeat: (v) => !/(.)\1\1/.test(v),
                nosequential: (v) => !hasSequentialRun(v),
                symbol: (v) => /[^A-Za-z0-9]/.test(v),
            };

            function initMeter(meter) {
                const field = document.getElementById(meter.dataset.passwordField);
                if (!field) return;

                const usernameField = meter.dataset.usernameField ? document.getElementById(meter.dataset.usernameField) : null;
                const staticUsername = meter.dataset.usernameValue || '';
                const segments = meter.querySelectorAll('.pw-strength-seg');
                const rows = meter.querySelectorAll('.pw-req');

                function currentUsername() {
                    return (usernameField ? usernameField.value : staticUsername || '').toLowerCase();
                }

                function update() {
                    const value = field.value;
                    let met = 0;
                    let total = 0;

                    rows.forEach((row) => {
                        const rule = row.dataset.rule;
                        let isMet;
                        if (rule === 'nousername') {
                            const uname = currentUsername();
                            isMet = uname.length < 3 || !value.toLowerCase().includes(uname);
                        } else {
                            isMet = RULES[rule] ? RULES[rule](value) : true;
                        }

                        row.querySelector('.pw-req-dot').classList.toggle('hidden', isMet);
                        row.querySelector('.pw-req-check').classList.toggle('hidden', !isMet);
                        row.querySelector('.pw-req-label').classList.toggle('text-emerald-400', isMet);
                        row.querySelector('.pw-req-label').classList.toggle('text-[#9c8f7b]', !isMet);

                        if (rule !== 'symbol') {
                            total++;
                            if (isMet) met++;
                        }
                    });

                    const ratio = total ? met / total : 0;
                    const filled = value.length === 0 ? 0 : Math.max(1, Math.round(ratio * segments.length));

                    segments.forEach((seg, i) => {
                        seg.style.background = i < filled ? SEQ_COLORS[Math.min(filled, SEQ_COLORS.length) - 1] : '';
                    });
                }

                field.addEventListener('input', update);
                if (usernameField) usernameField.addEventListener('input', update);
                update();
            }

            document.querySelectorAll('[data-password-strength]').forEach(initMeter);
        })();
    </script>
    @endpush
@endonce
