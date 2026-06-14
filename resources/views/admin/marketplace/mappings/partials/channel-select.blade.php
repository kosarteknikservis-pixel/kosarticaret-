<form method="get" class="admin-card p-4 mb-5 flex flex-wrap items-end gap-3">
    <div class="min-w-[180px] flex-1">
        <label class="admin-label">Pazaryeri kanalı</label>
        <select name="channel" class="admin-input" onchange="this.form.submit()">
            @foreach($channels as $channel)
                <option value="{{ $channel->key }}" @selected($channelKey === $channel->key)>{{ $channel->name }}</option>
            @endforeach
        </select>
    </div>
    @if(isset($search))
        <div class="min-w-[200px] flex-[2]">
            <label class="admin-label">Ara</label>
            <input type="search" name="q" value="{{ $search }}" placeholder="İsim ile filtrele…" class="admin-input">
        </div>
        <button type="submit" class="admin-btn admin-btn-secondary px-4 py-2.5">Filtrele</button>
    @endif
</form>
