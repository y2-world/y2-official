<div x-data="{
    open: false,
    selectedIndex: -1,
    venues: @entangle('venues'),
    init() {
        try {
            this.$watch('open', value => {
                if (value) {
                    this.selectedIndex = -1;
                }
            });
        } catch (e) {
            console.error('Alpine.js watch error:', e);
        }
    },
    selectVenue(venueName) {
        if (venueName) {
            window.location.href = '/venue?keyword=' + encodeURIComponent(venueName);
        }
    },
    navigateDown() {
        if (this.venues && this.selectedIndex < this.venues.length - 1) {
            this.selectedIndex++;
        }
    },
    navigateUp() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        }
    },
    selectCurrent() {
        if (this.venues && this.selectedIndex >= 0 && this.selectedIndex < this.venues.length) {
            this.selectVenue(this.venues[this.selectedIndex].name);
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
        placeholder="会場を検索..."
        autocomplete="off">

    <div
        x-show="open && Array.isArray(venues) && venues.length > 0"
        x-transition
        style="position: absolute; top: 100%; left: 0; width: auto; min-width: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; margin-top: 4px; max-height: 190px; overflow-y: auto; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <template x-for="(venue, index) in (Array.isArray(venues) ? venues : [])" :key="venue.name || index">
            <div
                @click="selectVenue(venue.name)"
                :class="{ 'bg-gray-100': index === selectedIndex }"
                class="song-search-suggestion"
                style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; color: black; text-align: left;"
                @mouseenter="selectedIndex = index">
                <span x-text="venue.name"></span>
            </div>
        </template>
    </div>
</div>

