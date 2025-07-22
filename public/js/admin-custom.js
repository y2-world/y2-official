function insertNumberLabels(prefix, labelClass) {
    const inputs = document.querySelectorAll(`input[name^="${prefix}["][name$="[song]"]`);

    inputs.forEach((input, index) => {
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;

        // 既存の番号ラベルがあれば削除
        const existing = inputGroup.previousElementSibling;
        if (existing && existing.classList.contains(labelClass)) {
            existing.remove();
        }

        // ラベル要素を作成して挿入
        const label = document.createElement('div');
        label.textContent = `${index + 1}.`;
        label.className = labelClass;
        label.style.marginBottom = '4px';
        label.style.fontWeight = 'bold';
        label.style.fontSize = '13px';
        label.style.color = '#666';

        inputGroup.parentNode.insertBefore(label, inputGroup);
    });
}

function updateAllNumberLabels() {
    insertNumberLabels('setlist', 'setlist-number-label');
    insertNumberLabels('encore', 'encore-number-label');
    insertNumberLabels('fes_setlist', 'fes-setlist-number-label');
    insertNumberLabels('fes_encore', 'fes-encore-number-label');
}

// 初回実行と動的追加に対応
document.addEventListener('DOMContentLoaded', () => {
    updateAllNumberLabels();

    ['.has-many-setlist', '.has-many-encore', '.has-many-fes_setlist', '.has-many-fes_encore'].forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
            container.addEventListener('click', () => {
                setTimeout(updateAllNumberLabels, 100);
            });
        }
    });
});