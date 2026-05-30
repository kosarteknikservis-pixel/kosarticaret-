@extends('layouts.admin')
@section('title', 'Müşteri hesapları')

@section('content')
    <x-admin.page-header
        title="Müşteri hesapları"
        :subtitle="$totalCustomers.' kayıtlı mağaza hesabı — /kayit ile oluşturulanlar'"
    />

    <form method="get" class="admin-card p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[12rem]">
            <label class="admin-label">Ara</label>
            <input type="search" name="q" value="{{ $search }}" placeholder="Ad veya e-posta" class="admin-input">
        </div>
        <button type="submit" class="admin-btn admin-btn-secondary">Filtrele</button>
        @if($search !== '')
            <a href="{{ route('admin.customers.index') }}" class="admin-btn admin-btn-secondary">Temizle</a>
        @endif
    </form>

    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ad</th>
                    <th>E-posta</th>
                    <th>Kayıt</th>
                    <th>Sipariş</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td class="font-semibold text-slate-900">{{ $customer->name }}</td>
                        <td class="font-mono text-xs text-slate-600">{{ $customer->email }}</td>
                        <td class="text-slate-500 text-xs whitespace-nowrap">{{ $customer->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($customer->orders_count > 0)
                                <span class="rounded-full bg-teal-50 text-teal-800 px-2.5 py-0.5 text-xs font-bold">{{ $customer->orders_count }}</span>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-slate-500">
                            @if($search !== '')
                                Aramanızla eşleşen müşteri yok.
                            @else
                                Henüz mağaza kaydı yok. Müşteriler <strong>/kayit</strong> sayfasından hesap açtığında burada listelenir.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
