{{-- resources/views/components/item-code-input.blade.php --}}
@props([
    'name', // name untuk input text (mis: bundles[0][finished_item_code])
    'hiddenName', // name untuk hidden finished_item_id
    'value' => '', // nilai kode awal (misal saat edit / old)
    'itemId' => null, // nilai item_id awal
    'items' => collect(),
    'placeholder' => 'Kode / nama item',
])

@php
    use Illuminate\Support\Str;
    // id unik untuk input (kalau mau di-query JS spesifik)
    $inputId = $attributes->get('id') ?? 'item-code-' . Str::random(6);
@endphp

<div class="position-relative">
    <input type="text" id="{{ $inputId }}" name="{{ $name }}" value="{{ $value }}"
        list="cutting-item-code-list" autocomplete="off"
        {{ $attributes->merge(['class' => 'form-control form-control-sm js-item-code-input']) }}
        placeholder="{{ $placeholder }}">

    {{-- hidden: item_id yang sebenarnya dikirim ke backend --}}
    <input type="hidden" name="{{ $hiddenName }}" value="{{ $itemId }}" class="js-item-id-input">
</div>

@once
    {{-- Datalist master: hanya output sekali walaupun komponen dipakai banyak kali --}}
    <datalist id="cutting-item-code-list">
        @foreach ($items as $item)
            <option value="{{ $item->code }}" data-item-id="{{ $item->id }}" data-item-name="{{ $item->name }}"
                data-item-category="{{ $item->category->name ?? '' }}">
                {{ $item->code }} — {{ $item->name }}
            </option>
        @endforeach
    </datalist>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const datalist = document.getElementById('cutting-item-code-list');
                if (!datalist) return;

                const options = Array.from(datalist.options);

                function syncItemId(input) {
                    const hidden = input.parentElement.querySelector('.js-item-id-input');
                    if (!hidden) return;

                    const val = (input.value || '').trim();
                    const found = options.find(opt => opt.value === val);

                    hidden.value = found ? (found.dataset.itemId || '') : '';
                }

                function attach(input) {
                    input.addEventListener('change', function() {
                        syncItemId(this);
                    });
                    input.addEventListener('blur', function() {
                        syncItemId(this);
                    });
                }

                document.querySelectorAll('.js-item-code-input').forEach(attach);

                // expose untuk dipakai di row dinamis (jika perlu)
                window.attachItemCodeInput = attach;
                window.syncItemCodeInput = syncItemId;
            });
        </script>
    @endpush
@endonce
