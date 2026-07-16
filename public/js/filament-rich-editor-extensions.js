/**
 * Trix (Filament RichEditor) の標準ツールバーには文字色・文字サイズの指定機能が無いため、
 * 独自のテキスト属性 (color / fontSizeSmall / fontSizeLarge / fontSizeXLarge) を追加する。
 * Trix は AlpineComponent として遅延読み込みされるため、window.Trix が代入された
 * 瞬間を捕捉して Trix.config が使えるようになった直後（カスタムエレメント初期化前）に設定する。
 */
(function () {
    function configureTrix(Trix) {
        if (Trix.__y2ExtraAttributesConfigured) {
            return;
        }
        Trix.__y2ExtraAttributesConfigured = true;

        Trix.config.textAttributes.color = {
            styleProperty: 'color',
            inheritable: true,
        };

        Trix.config.textAttributes.fontSizeSmall = {
            style: { fontSize: '0.75rem' },
            inheritable: true,
            parser: function (element) {
                return element.style.fontSize === '0.75rem';
            },
        };

        Trix.config.textAttributes.fontSizeLarge = {
            style: { fontSize: '1.5rem' },
            inheritable: true,
            parser: function (element) {
                return element.style.fontSize === '1.5rem';
            },
        };

        Trix.config.textAttributes.fontSizeXLarge = {
            style: { fontSize: '2rem' },
            inheritable: true,
            parser: function (element) {
                return element.style.fontSize === '2rem';
            },
        };
    }

    if (window.Trix) {
        configureTrix(window.Trix);
        return;
    }

    Object.defineProperty(window, 'Trix', {
        configurable: true,
        enumerable: true,
        get: function () {
            return this._y2Trix;
        },
        set: function (value) {
            configureTrix(value);
            this._y2Trix = value;
        },
    });
})();
