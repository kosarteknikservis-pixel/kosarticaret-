@props(['deleteAction' => null, 'deleteConfirm' => 'Silinsin mi?'])

<div class="admin-form-actions">
    <button type="submit" class="admin-btn admin-btn-primary px-8 py-2.5">{{ $slot->isEmpty() ? 'Kaydet' : $slot }}</button>
    {{ $actions ?? '' }}
</div>

@if($deleteAction)
    @push('admin-form-delete')
        <form method="post" action="{{ $deleteAction }}" class="admin-delete-form max-w-2xl mt-4" onsubmit="return confirm(@js($deleteConfirm))">
            @csrf
            @method('DELETE')
            <button type="submit" class="admin-btn admin-btn-danger text-sm py-2">Kalıcı olarak sil</button>
        </form>
    @endpush
@endif
