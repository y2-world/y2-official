{{-- スタンプ帳の見た目の切り替え：Stamps（マス目）／Hanko（大塚愛『愛 am BEST』の裏ジャケのような、はんこと曲名の表）。
     同じ台紙（.stamp-book）の見た目だけを変えるので、絞り込み・曲順・曲名の表記はどちらでも同じ。開いている方は ?view=hanko で URL に残す --}}
<div class="stats-tab-bar stamp-view-tabs">
    <button type="button" class="stats-tab-btn is-active" data-stamp-view="stamps">Stamps</button>
    <button type="button" class="stats-tab-btn" data-stamp-view="hanko">Hanko</button>
</div>
{{-- Hanko のはんこの、朱肉のかすれ（輪郭を少し揺らして、ところどころ薄くする） --}}
<svg width="0" height="0" style="position: absolute;" aria-hidden="true" focusable="false">
    <filter id="hankoInk" x="-10%" y="-10%" width="120%" height="120%">
        <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="7" result="noise"/>
        <feDisplacementMap in="SourceGraphic" in2="noise" scale="1.2" xChannelSelector="R" yChannelSelector="G" result="rough"/>
        <feTurbulence type="fractalNoise" baseFrequency="0.35" numOctaves="1" seed="3" result="spots"/>
        <feColorMatrix in="spots" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 -0.9 1.25" result="mask"/>
        <feComposite in="rough" in2="mask" operator="in"/>
    </filter>
</svg>
