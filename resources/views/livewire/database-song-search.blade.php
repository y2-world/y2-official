<div x-data="{
    open: false,
    selectedIndex: -1,
    songs: @entangle('songs').live,
    init() {
        this.$watch('open', value => {
            if (value) {
                this.selectedIndex = -1;
            }
        });
    },
    selectSong(songId) {
        window.location.href = '/database/songs/' + songId;
    },
    navigateDown() {
        if (this.selectedIndex < this.songs.length - 1) {
            this.selectedIndex++;
        }
    },
    navigateUp() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        }
    },
    selectCurrent() {
        if (this.selectedIndex >= 0 && this.selectedIndex < this.songs.length) {
            this.selectSong(this.songs[this.selectedIndex].id);
        }
    }
}" class="search-wrapper" style="position: relative;">
    <input
        type="text"
        wire:model.live.debounce.300ms="search"
        @focus="open = true"
        @blur="setTimeout(() => open = false, 200)"
        @keydown.down.prevent="navigateDown()"
        @keydown.up.prevent="navigateUp()"
        @keydown.enter.prevent="selectCurrent()"
        class="database-search-input"
        placeholder="楽曲を検索..."
        autocomplete="off">

    <div
        x-show="open && songs.length > 0"
        x-transition
        style="position: absolute; top: 100%; left: 0; width: auto; min-width: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; margin-top: 4px; max-height: 150px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <template x-for="(song, index) in songs" :key="song.id">
            <div
                @click="selectSong(song.id)"
                :class="{ 'bg-gray-100': index === selectedIndex }"
                style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; color: black; text-align: left; font-size: 8px;"
                @mouseenter="selectedIndex = index">
                <span x-text="song.title"></span>
            </div>
        </template>
    </div>
</div>
