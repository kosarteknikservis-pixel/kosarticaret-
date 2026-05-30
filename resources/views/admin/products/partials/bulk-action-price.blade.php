@props([
    'prefix',
    'label',
    'allowClear' => false,
])

<details class="bulk-action-block">
    <summary class="bulk-action-block__head">
        <label class="admin-checkbox bulk-action-block__check" onclick="event.stopPropagation()">
            <input type="checkbox" name="act_{{ $prefix }}" value="1">
        </label>
        <span>{{ $label }}</span>
    </summary>
    <div class="bulk-action-block__body grid sm:grid-cols-2 gap-3">
        <select name="{{ $prefix }}_mode" class="admin-input">
            <option value="set">Sabit değer</option>
            <option value="add_percent">Yüzde artır (+)</option>
            <option value="subtract_percent">Yüzde indir (−)</option>
            <option value="add_fixed">Tutar ekle (+₺)</option>
            <option value="subtract_fixed">Tutar çıkar (−₺)</option>
            @if($prefix === 'price')
                <option value="round_99">.99’a yuvarla (ör. 1249.99)</option>
            @endif
            @if($allowClear)
                <option value="clear">İndirimli fiyatı kaldır</option>
            @endif
        </select>
        <input type="number" step="0.01" name="{{ $prefix }}_value" value="0" class="admin-input" placeholder="Değer">
    </div>
</details>
