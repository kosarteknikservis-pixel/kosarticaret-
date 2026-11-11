@props(['deleteAction' => null, 'deleteConfirm' => 'Silinsin mi?'])

<div class="admin-form-actions">
    <x-admin.button variant="primary" type="submit">
        {{ $slot->isEmpty() ? 'Kaydet' : $slot }}
    </x-admin.button>
    {{ $actions ?? '' }}
</div>

@if($deleteAction)
    @push('admin-form-delete')
        <form method="post" action="{{ $deleteAction }}" class="admin-delete-form max-w-2xl mt-4" onsubmit="return confirm(@js($deleteConfirm))">
            @csrf
            @method('DELETE')
            <x-admin.button variant="danger" size="sm" type="submit">Kalıcı olarak sil</x-admin.button>
        </form>
    @endpush
@endif
