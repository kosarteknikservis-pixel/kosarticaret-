@extends('layouts.admin')
@section('title', 'Bülten')

@section('content')
    <x-admin.page-header title="Bülten aboneleri" :subtitle="$subscribers->total().' kayıtlı e-posta'" />
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>E-posta</th><th>Kayıt tarihi</th></tr></thead>
            <tbody>
                @forelse($subscribers as $sub)
                    <tr>
                        <td class="font-medium">{{ $sub->email }}</td>
                        <td class="text-slate-500">{{ $sub->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center py-10 text-slate-500">Henüz abone yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($subscribers->hasPages())<div class="p-4 border-t">{{ $subscribers->links() }}</div>@endif
    </div>
@endsection
