{{-- resources/views/components/item-suggest-input.blade.php --}}
@props([
    // name untuk input text (kode item)
    'codeName',

    // name untuk hidden item_id
    'idName',

    // list item master (id, code, name)
    'items' => collect(),

    // nilai awal (untuk edit / old)
    'codeValue' => '',
    'idValue' => '',

    'placeholder' => 'Kode / nama item',
])

@php
    use Illuminate\Support\Str;

    $uid = 'item-suggest-' . Str::random(6);

    // data minimal untuk JS (id, code, name)
    $jsItems = $items
        ->map(
            fn($it) => [
                'id' => $it->id,
                'code' => $it->code,
                'name' => $it->name,
            ],
        )
        ->values();
@endphp

<div class="item-suggest-wrap position-relative" data-uid="{{ $uid }}">
    <input type="text" id="{{ $uid }}" name="{{ $codeName }}" value="{{ $codeValue }}" autocomplete="off"
        class="form-control form-control-sm js-item-suggest-input" placeholder="{{ $placeholder }}"
        data-items='@json($jsItems)'>

    <input type="hidden" name="{{ $idName }}" value="{{ $idValue }}" class="js-item-suggest-id">

    <div class="item-suggest-dropdown shadow-sm" style="display:none;"></div>
</div>

@once
    @push('head')
        <style>
            .item-suggest-dropdown {
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                margin-top: 2px;
                background: var(--card, #fff);
                border: 1px solid var(--line, #e5e7eb);
                border-radius: 6px;
                max-height: 220px;
                overflow-y: auto;
                z-index: 2020;
            }

            .item-suggest-option {
                padding: .35rem .6rem;
                width: 100%;
                border: 0;
                background: transparent;
                text-align: left;
                cursor: pointer;
                font-size: .84rem;
            }

            .item-suggest-option:hover {
                background: color-mix(in srgb, var(--primary, #3b82f6) 10%, transparent 90%);
            }

            .item-suggest-option-code {
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }

            .item-suggest-option-name {
                font-size: .78rem;
                color: var(--muted, #6b7280);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function initItemSuggestWrap(wrap) {
                    if (!wrap) return;

                    const input = wrap.querySelector('.js-item-suggest-input');
                    const hidden = wrap.querySelector('.js-item-suggest-id');
                    const dropdown = wrap.querySelector('.item-suggest-dropdown');

                    if (!input || !dropdown) return;

                    let items = [];
                    try {
                        items = JSON.parse(input.getAttribute('data-items') || '[]');
                    } catch (e) {
                        items = [];
                    }

                    function buildSuggestions() {
                        const qRaw = (input.value || '').trim();
                        dropdown.innerHTML = '';

                        if (!qRaw) {
                            dropdown.style.display = 'none';
                            return;
                        }

                        const q = qRaw.toLowerCase();
                        const matches = items.filter(it =>
                            it.code.toLowerCase().includes(q) ||
                            (it.name && it.name.toLowerCase().includes(q))
                        ).slice(0, 8);

                        if (!matches.length) {
                            dropdown.style.display = 'none';
                            return;
                        }

                        matches.forEach(it => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'item-suggest-option';
                            btn.innerHTML = `
                                <div class="item-suggest-option-code">${it.code}</div>
                                <div class="item-suggest-option-name">${it.name ?? ''}</div>
                            `;
                            btn.addEventListener('click', function() {
                                input.value = it.code;
                                if (hidden) hidden.value = it.id;
                                dropdown.style.display = 'none';
                            });
                            dropdown.appendChild(btn);
                        });

                        dropdown.style.display = 'block';
                    }

                    input.addEventListener('input', function() {
                        if (hidden) hidden.value = '';
                        buildSuggestions();
                    });

                    input.addEventListener('focus', function() {
                        buildSuggestions();
                    });

                    input.addEventListener('blur', function() {
                        setTimeout(() => dropdown.style.display = 'none', 150);
                    });
                }

                // init semua komponen yang sudah ada di DOM
                document.querySelectorAll('.item-suggest-wrap').forEach(initItemSuggestWrap);

                // expose untuk row dinamis (addRow)
                window.initItemSuggestInput = initItemSuggestWrap;
            });
        </script>
    @endpush
@endonce
