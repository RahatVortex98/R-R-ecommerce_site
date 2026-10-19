@php($slides=$settings->heroSlides())
<div class="hero-art hero-carousel {{ count($slides)>1?'has-controls':'' }}" role="region" aria-roledescription="carousel" aria-label="Featured care banners"
    x-data="{
        active: 0, count: {{ count($slides) }}, paused: false, hovering: false, focused: false, timer: null, motion: null, motionListener: null,
        init() {
            this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.paused = this.motion.matches;
            this.motionListener = event => { if (event.matches) this.paused = true; };
            this.motion.addEventListener('change', this.motionListener);
            if (this.count > 1) this.timer = setInterval(() => {
                if (!this.paused && !this.hovering && !this.focused && !document.hidden) this.active = (this.active + 1) % this.count;
            }, 5000);
        },
        go(index) { this.active = (index + this.count) % this.count; },
        destroy() { clearInterval(this.timer); this.motion?.removeEventListener('change', this.motionListener); }
    }"
    @mouseenter="hovering=true" @mouseleave="hovering=false"
    @focusin="focused=true" @focusout="focused=$el.contains($event.relatedTarget)"
    @keydown.arrow-left.prevent="go(active-1)" @keydown.arrow-right.prevent="go(active+1)">
    <div class="hero-slides" :aria-live="paused || focused ? 'polite' : 'off'">
        @foreach($slides as $index=>$slide)
            <div class="hero-slide" role="group" aria-roledescription="slide" aria-label="{{ $index+1 }} of {{ count($slides) }}"
                x-show="active === {{ $index }}" @if($index>0) x-cloak @endif>
                <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}" width="600" height="600" @if($index===0) fetchpriority="high" @else loading="lazy" @endif>
            </div>
        @endforeach
    </div>
    <span class="art-label">THE ART OF EVERYDAY CARE</span>
    @if(count($slides)>1)
        <div class="hero-carousel-controls">
            <button type="button" @click="go(active-1)" aria-label="Previous banner">‹</button>
            <div class="hero-carousel-dots" role="group" aria-label="Choose a banner">
                @foreach($slides as $index=>$slide)
                    <button type="button" class="hero-carousel-dot" @click="go({{ $index }})" :aria-pressed="active === {{ $index }}" aria-label="Show banner {{ $index+1 }}"></button>
                @endforeach
            </div>
            <button type="button" @click="go(active+1)" aria-label="Next banner">›</button>
            <button type="button" class="hero-carousel-toggle" @click="paused=!paused" :aria-label="paused ? 'Play slideshow' : 'Pause slideshow'" x-text="paused ? 'Play' : 'Pause'">Pause slideshow</button>
        </div>
    @endif
</div>
