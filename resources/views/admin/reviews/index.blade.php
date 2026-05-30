@extends('layouts.admin')
@section('title', 'Yorumlar')

@section('content')
    <x-admin.page-header title="Ürün yorumları" subtitle="Onay bekleyenler vitrinde görünmez" />
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead><tr><th>Yorum</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td class="max-w-xl">
                            <p class="font-semibold text-slate-900">{{ $review->product?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $review->author_name }} · {{ $review->email }}</p>
                            <p class="mt-1 text-amber-500 text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                            @if($review->title)<p class="text-sm font-medium mt-1">{{ $review->title }}</p>@endif
                            <p class="mt-1 text-sm text-slate-600">{{ Str::limit($review->body, 200) }}</p>
                            <p class="text-xs text-slate-400 mt-2">{{ $review->created_at->format('d.m.Y H:i') }}</p>
                        </td>
                        <td class="align-top">
                            <span class="admin-badge {{ $review->approved ? 'admin-badge-success' : 'admin-badge-warning' }}">{{ $review->approved ? 'Yayında' : 'Bekliyor' }}</span>
                        </td>
                        <td class="align-top space-y-2">
                            @if(!$review->approved)
                                <form method="post" action="{{ route('admin.reviews.approve', $review) }}">@csrf @method('PATCH')
                                    <button type="submit" class="admin-btn admin-btn-primary text-xs py-1.5 w-full">Onayla</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger text-xs py-1.5 w-full">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-10 text-slate-500">Yorum yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($reviews->hasPages())<div class="p-4 border-t">{{ $reviews->links() }}</div>@endif
    </div>
@endsection
